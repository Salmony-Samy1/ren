<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\PaymentTransaction;
use App\Models\SavedCard;
use Illuminate\Support\Str;

class TapGatewayService
{
    protected string $baseUrl;
    protected string $secretKey;
    protected string $publicKey;
    protected int $maxRetries;
    protected int $retryDelay;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.tap.base_url', 'https://api.tap.company/v2'), '/');
        $this->secretKey = (string) config('services.tap.secret_key', '');
        $this->publicKey = (string) config('services.tap.public_key', '');
        $this->maxRetries = config('services.tap.max_retries', 3);
        $this->retryDelay = config('services.tap.retry_delay', 1000); // milliseconds
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * إرسال طلب HTTP مع إعادة المحاولة ومعالجة الأخطاء
     */
    protected function makeHttpRequest(string $method, string $endpoint, array $data = [], array $options = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            try {
                $attempt++;
                
                // إضافة Idempotency Key لمنع التكرار
                $headers = $this->headers();
                if (isset($options['idempotency_key'])) {
                    $headers['Idempotency-Key'] = $options['idempotency_key'];
                }

                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->retry(0) // نتعامل مع إعادة المحاولة يدوياً
                    ->$method($url, $data);

                if ($response->successful()) {
                    $this->logApiRequest($method, $endpoint, $data, $response->json(), $attempt);
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'status' => $response->status()
                    ];
                }

                // معالجة أخطاء HTTP
                $errorData = $this->handleHttpError($response, $attempt);
                if ($errorData['should_retry'] && $attempt < $this->maxRetries) {
                    $this->logRetryAttempt($method, $endpoint, $attempt, $errorData['message']);
                    usleep($this->retryDelay * 1000); // تحويل إلى microseconds
                    continue;
                }

                return $errorData;

            } catch (\Exception $e) {
                $lastError = $e;
                $this->logApiError($method, $endpoint, $data, $e->getMessage(), $attempt);
                
                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000);
                    continue;
                }
            }
        }

        return [
            'success' => false,
            'message' => 'Max retries exceeded',
            'error' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'attempts' => $attempt
        ];
    }

    /**
     * معالجة أخطاء HTTP وتحديد ما إذا كان يجب إعادة المحاولة
     */
    protected function handleHttpError($response, int $attempt): array
    {
        $statusCode = $response->status();
        $body = $response->body();
        $json = $response->json();

        // أخطاء يجب إعادة المحاولة فيها
        $retryableErrors = [408, 429, 500, 502, 503, 504];
        
        if (in_array($statusCode, $retryableErrors)) {
            return [
                'success' => false,
                'should_retry' => true,
                'message' => "HTTP {$statusCode}: {$body}",
                'status_code' => $statusCode,
                'error_code' => $json['error_code'] ?? null
            ];
        }

        // أخطاء لا يجب إعادة المحاولة فيها
        return [
            'success' => false,
            'should_retry' => false,
            'message' => $this->getErrorMessage($statusCode, $json),
            'status_code' => $statusCode,
            'error_code' => $json['error_code'] ?? null,
            'raw_response' => $json
        ];
    }

    /**
     * الحصول على رسالة خطأ مناسبة للمستخدم
     */
    protected function getErrorMessage(int $statusCode, array $response): string
    {
        $errorCode = $response['error_code'] ?? null;
        $message = $response['message'] ?? 'Unknown error';

        // رسائل خطأ محددة حسب كود الخطأ
        $errorMessages = [
            'INSUFFICIENT_FUNDS' => 'الرصيد غير كافي',
            'INVALID_CARD' => 'بيانات البطاقة غير صحيحة',
            'CARD_EXPIRED' => 'انتهت صلاحية البطاقة',
            'CARD_DECLINED' => 'تم رفض البطاقة',
            'INVALID_CVV' => 'رمز الأمان غير صحيح',
            'INVALID_EXPIRY' => 'تاريخ الانتهاء غير صحيح',
            'CARD_NOT_SUPPORTED' => 'نوع البطاقة غير مدعوم',
            'TRANSACTION_LIMIT_EXCEEDED' => 'تم تجاوز الحد المسموح للعملية',
            'DAILY_LIMIT_EXCEEDED' => 'تم تجاوز الحد اليومي',
            'MONTHLY_LIMIT_EXCEEDED' => 'تم تجاوز الحد الشهري',
        ];

        if (isset($errorMessages[$errorCode])) {
            return $errorMessages[$errorCode];
        }

        // رسائل عامة حسب رمز الاستجابة
        switch ($statusCode) {
            case 400:
                return 'بيانات الطلب غير صحيحة';
            case 401:
                return 'خطأ في المصادقة';
            case 403:
                return 'غير مسموح بالوصول';
            case 404:
                return 'الخدمة غير متوفرة';
            case 422:
                return 'بيانات غير صالحة';
            default:
                return $message;
        }
    }

    /**
     * تسجيل طلبات API الناجحة
     */
    protected function logApiRequest(string $method, string $endpoint, array $data, array $response, int $attempt): void
    {
        Log::channel('payment')->info('Tap API Request Success', [
            'method' => $method,
            'endpoint' => $endpoint,
            'attempt' => $attempt,
            'request_data' => $this->sanitizeRequestData($data),
            'response_status' => $response['status'] ?? 'unknown',
            'transaction_id' => $response['id'] ?? null,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * تسجيل أخطاء API
     */
    protected function logApiError(string $method, string $endpoint, array $data, string $error, int $attempt): void
    {
        Log::channel('payment')->error('Tap API Request Error', [
            'method' => $method,
            'endpoint' => $endpoint,
            'attempt' => $attempt,
            'request_data' => $this->sanitizeRequestData($data),
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * تسجيل محاولات إعادة المحاولة
     */
    protected function logRetryAttempt(string $method, string $endpoint, int $attempt, string $reason): void
    {
        Log::channel('payment')->warning('Tap API Retry Attempt', [
            'method' => $method,
            'endpoint' => $endpoint,
            'attempt' => $attempt,
            'reason' => $reason,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * تنظيف بيانات الطلب من المعلومات الحساسة
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = ['cvc', 'cvv', 'card_number', 'number', 'token'];
        $sanitized = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '***';
            }
        }

        return $sanitized;
    }

    /**
     * إنشاء Idempotency Key فريد
     */
    protected function generateIdempotencyKey(string $prefix = 'payment'): string
    {
        return $prefix . '_' . Str::uuid() . '_' . time();
    }

    /**
     * إنشاء عميل جديد في Tap
     */
    public function createCustomer(User $user): array
    {
        try {
            $payload = [
                'first_name' => $user->first_name ?? 'Customer',
                'last_name' => $user->last_name ?? 'User',
                'email' => $user->email,
                'phone' => [
                    'country_code' => $user->country_code ?? '966',
                    'number' => $user->phone ?? '000000000'
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'platform' => 'gathro'
                ]
            ];

            $result = $this->makeHttpRequest('POST', '/customers', $payload, [
                'idempotency_key' => $this->generateIdempotencyKey('customer_' . $user->id)
            ]);

            if ($result['success']) {
                return [
                    'success' => true,
                    'customer_id' => $result['data']['id'] ?? null,
                    'data' => $result['data']
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to create customer',
                'error' => $result['error'] ?? null,
                'code' => $result['status_code'] ?? 500,
            ];

        } catch (\Throwable $e) {
            Log::channel('payment')->error('Tap createCustomer exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Customer creation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * إنشاء شحنة جديدة مع Tap
     */
    public function createCharge(array $payload): array
    {
        try {
            // إضافة Idempotency Key لمنع التكرار
            $idempotencyKey = $this->generateIdempotencyKey('charge');
            
            // إضافة Idempotency Key كـ header (ليس في payload)
            $result = $this->makeHttpRequest('POST', '/charges', $payload, [
                'idempotency_key' => $idempotencyKey
            ]);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Tap charge failed',
                    'error' => $result['error'] ?? null,
                    'code' => $result['status_code'] ?? 500,
                ];
            }

            $json = $result['data'];
            $status = $json['status'] ?? 'UNKNOWN';
            $requiresAction = isset($json['transaction']['url']) || isset($json['redirect_url']);
            $redirectUrl = $json['transaction']['url'] ?? ($json['redirect_url'] ?? null);

            return [
                'success' => in_array($status, ['CAPTURED', 'AUTHORIZED']),
                'status' => $status,
                'transaction_id' => $json['id'] ?? null,
                'requires_action' => $requiresAction,
                'redirect_url' => $redirectUrl,
                'raw' => $json,
                'idempotency_key' => $idempotencyKey
            ];

        } catch (\Throwable $e) {
            Log::channel('payment')->error('Tap createCharge exception', [
                'payload' => $this->sanitizeRequestData($payload),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Tap charge exception',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * معالجة دفع البطاقات الائتمانية (Visa/MasterCard)
     */
    public function processCardPayment(array $data, PaymentTransaction $transaction): array
    {
        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'SAR',
            'source' => [
                'id' => $data['tap_token'] // Token من Card SDK v2
            ],
            'customer' => [
                'id' => $data['customer_id'] ?? null,
                'first_name' => auth()->user()->first_name ?? 'Customer',
                'last_name' => auth()->user()->last_name ?? 'User',
                'email' => auth()->user()->email,
                'phone' => [
                    'country_code' => auth()->user()->country_code ?? '966',
                    'number' => auth()->user()->phone ?? '000000000'
                ]
            ],
            'save_card' => $data['save_card'] ?? false,
            'reference' => [
                'transaction' => $transaction->id
            ],
            'metadata' => [
                'user_id' => auth()->id(),
                'booking_id' => $data['booking_id'] ?? null,
                'platform' => 'gathro'
            ],
            'receipt' => [
                'email' => true,
                'sms' => true
            ],
            'redirect' => [
                'url' => config('app.frontend_url') . '/payment/callback'
            ]
        ];

        return $this->createCharge($payload);
    }

    /**
     * معالجة دفع Apple Pay
     */
    public function processApplePayPayment(array $data, PaymentTransaction $transaction): array
    {
        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'SAR',
            'source' => [
                'id' => $data['tap_token'] // Token من Apple Pay Web SDK
            ],
            'customer' => [
                'id' => $data['customer_id'] ?? null,
                'first_name' => auth()->user()->first_name ?? 'Customer',
                'last_name' => auth()->user()->last_name ?? 'User',
                'email' => auth()->user()->email,
                'phone' => [
                    'country_code' => auth()->user()->country_code ?? '966',
                    'number' => auth()->user()->phone ?? '000000000'
                ]
            ],
            'reference' => [
                'transaction' => $transaction->id
            ],
            'metadata' => [
                'user_id' => auth()->id(),
                'booking_id' => $data['booking_id'] ?? null,
                'platform' => 'gathro',
                'payment_method' => 'apple_pay'
            ],
            'receipt' => [
                'email' => true,
                'sms' => true
            ]
        ];

        return $this->createCharge($payload);
    }

    /**
     * معالجة دفع Benefit
     */
    public function processBenefitPayment(array $data, PaymentTransaction $transaction): array
    {
        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'BHD',
            'source' => [
                'id' => 'src_bh.benefit'
            ],
            'customer' => [
                'id' => $data['customer_id'] ?? null,
                'first_name' => auth()->user()->first_name ?? 'Customer',
                'last_name' => auth()->user()->last_name ?? 'User',
                'email' => auth()->user()->email,
                'phone' => [
                    'country_code' => '973',
                    'number' => $data['phone_number'] ?? '00000000'
                ]
            ],
            'reference' => [
                'transaction' => $transaction->id
            ],
            'metadata' => [
                'user_id' => auth()->id(),
                'booking_id' => $data['booking_id'] ?? null,
                'platform' => 'gathro',
                'payment_method' => 'benefit'
            ],
            'receipt' => [
                'email' => true,
                'sms' => true
            ],
            'redirect' => [
                'url' => config('app.frontend_url') . '/payment/callback'
            ]
        ];

        return $this->createCharge($payload);
    }

    /**
     * معالجة دفع BenefitPay
     */
    public function processBenefitPayPayment(array $data, PaymentTransaction $transaction): array
    {
        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'BHD',
            'source' => [
                'id' => $data['tap_token'] // Token من BenefitPay SDK
            ],
            'customer' => [
                'id' => $data['customer_id'] ?? null,
                'first_name' => auth()->user()->first_name ?? 'Customer',
                'last_name' => auth()->user()->last_name ?? 'User',
                'email' => auth()->user()->email,
                'phone' => [
                    'country_code' => '973',
                    'number' => $data['phone_number'] ?? '00000000'
                ]
            ],
            'reference' => [
                'transaction' => $transaction->id
            ],
            'metadata' => [
                'user_id' => auth()->id(),
                'booking_id' => $data['booking_id'] ?? null,
                'platform' => 'gathro',
                'payment_method' => 'benefitpay'
            ],
            'receipt' => [
                'email' => true,
                'sms' => true
            ]
        ];

        return $this->createCharge($payload);
    }

    /**
     * معالجة دفع Google Pay
     */
    public function processGooglePayPayment(array $data, PaymentTransaction $transaction): array
    {
        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'SAR',
            'source' => [
                'id' => $data['tap_token'] // Token من Google Pay
            ],
            'customer' => [
                'id' => $data['customer_id'] ?? null,
                'first_name' => auth()->user()->first_name ?? 'Customer',
                'last_name' => auth()->user()->last_name ?? 'User',
                'email' => auth()->user()->email,
                'phone' => [
                    'country_code' => auth()->user()->country_code ?? '966',
                    'number' => auth()->user()->phone ?? '000000000'
                ]
            ],
            'reference' => [
                'transaction' => $transaction->id
            ],
            'metadata' => [
                'user_id' => auth()->id(),
                'booking_id' => $data['booking_id'] ?? null,
                'platform' => 'gathro',
                'payment_method' => 'google_pay'
            ],
            'receipt' => [
                'email' => true,
                'sms' => true
            ]
        ];

        return $this->createCharge($payload);
    }

    /**
     * إنشاء token من بطاقة محفوظة (MIT Flow)
     */
    public function createTokenFromSavedCard(string $customerId, string $cardId): array
    {
        try {
            $payload = [
                'customer' => $customerId,
                'card' => $cardId
            ];

            $response = Http::withHeaders($this->headers())
                ->post($this->baseUrl . '/tokens', $payload);

            if (!$response->ok()) {
                return [
                    'success' => false,
                    'message' => $response->json('message') ?? 'Failed to create token',
                    'error' => $response->body(),
                    'code' => $response->status(),
                ];
            }

            $data = $response->json();
            
            return [
                'success' => true,
                'token' => $data['id'] ?? null,
                'data' => $data
            ];

        } catch (\Throwable $e) {
            Log::error('Tap createTokenFromSavedCard error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Token creation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * الحصول على جميع البطاقات المحفوظة للعميل
     */
    public function getCustomerCards(string $customerId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get($this->baseUrl . '/customers/' . $customerId . '/cards');

            if (!$response->ok()) {
                return [
                    'success' => false,
                    'message' => $response->json('message') ?? 'Failed to get customer cards',
                    'error' => $response->body(),
                    'code' => $response->status(),
                ];
            }

            $data = $response->json();
            
            return [
                'success' => true,
                'cards' => $data['data'] ?? [],
                'data' => $data
            ];

        } catch (\Throwable $e) {
            Log::error('Tap getCustomerCards error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get customer cards',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * التحقق من صحة webhook signature
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        try {
            // إنشاء التوقيع المتوقع باستخدام raw body (كما هو مطلوب من Tap)
            $expectedSignature = hash_hmac('sha256', $rawBody, $this->secretKey);
            
            // استخدام hash_equals لمنع timing attacks
            $isValid = hash_equals($expectedSignature, $signature);
            
            // تسجيل محاولة التحقق
            Log::channel('webhook')->info('Webhook signature verification', [
                'is_valid' => $isValid,
                'payload_hash' => hash('sha256', $rawBody),
                'timestamp' => now()->toISOString()
            ]);
            
            return $isValid;
            
        } catch (\Exception $e) {
            Log::channel('webhook')->error('Webhook signature verification error', [
                'error' => $e->getMessage(),
                'signature_provided' => !empty($signature),
                'timestamp' => now()->toISOString()
            ]);
            
            return false;
        }
    }

    /**
     * الحصول على Public Key للاستخدام في Frontend
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * الحصول على إعدادات Apple Pay
     */
    public function getApplePayConfig(): array
    {
        return [
            'domain' => config('services.tap.apple_pay_domain'),
            'merchant_ids' => config('services.tap.apple_pay_merchant_ids'),
            'public_key' => $this->publicKey
        ];
    }
}
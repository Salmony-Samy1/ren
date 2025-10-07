<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymentRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();
        $ip = $request->ip();
        
        // تحديد الحدود حسب نوع المستخدم
        $limits = $this->getRateLimits($user);
        
        // فحص حد الطلبات لكل مستخدم
        if ($user) {
            $userKey = "payment_rate_limit_user_{$user->id}";
            if (!$this->checkRateLimit($userKey, $limits['per_user'])) {
                Log::channel('security')->warning('Payment rate limit exceeded for user', [
                    'user_id' => $user->id,
                    'ip' => $ip,
                    'endpoint' => $request->path(),
                    'timestamp' => now()->toISOString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'تم تجاوز الحد المسموح من طلبات الدفع. يرجى المحاولة لاحقاً.',
                    'retry_after' => $limits['per_user']['window']
                ], 429);
            }
        }
        
        // فحص حد الطلبات لكل IP
        $ipKey = "payment_rate_limit_ip_{$ip}";
        if (!$this->checkRateLimit($ipKey, $limits['per_ip'])) {
            Log::channel('security')->warning('Payment rate limit exceeded for IP', [
                'ip' => $ip,
                'user_id' => $user?->id,
                'endpoint' => $request->path(),
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز الحد المسموح من طلبات الدفع من هذا العنوان. يرجى المحاولة لاحقاً.',
                'retry_after' => $limits['per_ip']['window']
            ], 429);
        }
        
        // فحص حد محاولات البطاقات المرفوضة (Card Testing Protection)
        $cardTestingKey = "card_testing_protection_{$ip}";
        if (!$this->checkCardTestingLimit($cardTestingKey, $ip)) {
            Log::channel('security')->critical('Card testing detected', [
                'ip' => $ip,
                'user_id' => $user?->id,
                'endpoint' => $request->path(),
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'تم رفض الطلب لأسباب أمنية.',
                'retry_after' => 3600 // ساعة واحدة
            ], 429);
        }
        
        return $next($request);
    }
    
    /**
     * تحديد حدود المعدل حسب نوع المستخدم
     */
    private function getRateLimits($user): array
    {
        // حدود مختلفة حسب نوع المستخدم
        if ($user && $user->type === 'premium') {
            return [
                'per_user' => ['max_attempts' => 20, 'window' => 60], // 20 طلب في الدقيقة
                'per_ip' => ['max_attempts' => 50, 'window' => 60],   // 50 طلب في الدقيقة
            ];
        }
        
        if ($user && $user->type === 'provider') {
            return [
                'per_user' => ['max_attempts' => 15, 'window' => 60], // 15 طلب في الدقيقة
                'per_ip' => ['max_attempts' => 30, 'window' => 60],   // 30 طلب في الدقيقة
            ];
        }
        
        // المستخدمون العاديون
        return [
            'per_user' => ['max_attempts' => 10, 'window' => 60], // 10 طلبات في الدقيقة
            'per_ip' => ['max_attempts' => 20, 'window' => 60],   // 20 طلب في الدقيقة
        ];
    }
    
    /**
     * فحص حد المعدل
     */
    private function checkRateLimit(string $key, array $limit): bool
    {
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $limit['max_attempts']) {
            return false;
        }
        
        Cache::put($key, $attempts + 1, $limit['window']);
        return true;
    }
    
    /**
     * فحص حد محاولات البطاقات المرفوضة (Card Testing Protection)
     */
    private function checkCardTestingLimit(string $key, string $ip): bool
    {
        $failedAttempts = Cache::get($key, 0);
        
        // إذا تجاوز 5 محاولات فاشلة في ساعة واحدة، حظر لمدة ساعة
        if ($failedAttempts >= 5) {
            return false;
        }
        
        return true;
    }
    
    /**
     * تسجيل محاولة فاشلة للبطاقة (يتم استدعاؤها من PaymentService)
     */
    public static function recordFailedCardAttempt(string $ip): void
    {
        $key = "card_testing_protection_{$ip}";
        $attempts = Cache::get($key, 0);
        Cache::put($key, $attempts + 1, 3600); // ساعة واحدة
    }
}
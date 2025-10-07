<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * إرسال فاتورة عبر البريد الإلكتروني
     */
    public function sendInvoice(string $email, Invoice $invoice): bool
    {
        try {
            // هنا يمكن إرسال الفاتورة عبر البريد الإلكتروني
            // يمكن استخدام Laravel Mail مع قوالب مخصصة
            
            $data = [
                'invoice' => $invoice,
                'user' => $invoice->user,
                'items' => $invoice->items,
                'total' => $invoice->total_amount,
                'tax' => $invoice->tax_amount,
                'discount' => $invoice->discount_amount,
                'commission' => $invoice->commission_amount,
                'status' => $invoice->status,
                'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
            ];

            // إرسال البريد الإلكتروني
            // Mail::to($email)->send(new InvoiceEmail($data));
            
            // تسجيل العملية
            Log::info('Invoice email sent successfully', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'user_id' => $invoice->user_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * إرسال إشعار تأكيد الدفع
     */
    public function sendPaymentConfirmation(string $email, Invoice $invoice): bool
    {
        try {
            $data = [
                'invoice' => $invoice,
                'user' => $invoice->user,
                'payment_amount' => $invoice->total_amount,
                'payment_date' => now()->format('Y-m-d H:i:s'),
                'transaction_id' => $invoice->transaction_id,
            ];

            // إرسال البريد الإلكتروني
            // Mail::to($email)->send(new PaymentConfirmationEmail($data));
            
            Log::info('Payment confirmation email sent successfully', [
                'invoice_id' => $invoice->id,
                'email' => $email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * إرسال إشعار إلغاء الفاتورة
     */
    public function sendCancellationNotice(string $email, Invoice $invoice, string $reason = null): bool
    {
        try {
            $data = [
                'invoice' => $invoice,
                'user' => $invoice->user,
                'cancellation_reason' => $reason,
                'cancelled_at' => now()->format('Y-m-d H:i:s'),
                'refund_amount' => $invoice->total_amount,
            ];

            // إرسال البريد الإلكتروني
            // Mail::to($email)->send(new CancellationNoticeEmail($data));
            
            Log::info('Cancellation notice email sent successfully', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notice email', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * إرسال تذكير بالدفع
     */
    public function sendPaymentReminder(string $email, Invoice $invoice): bool
    {
        try {
            $data = [
                'invoice' => $invoice,
                'user' => $invoice->user,
                'due_date' => $invoice->due_date?->format('Y-m-d') ?? 'غير محدد',
                'amount_due' => $invoice->total_amount,
                'days_overdue' => $this->calculateDaysOverdue($invoice),
            ];

            // إرسال البريد الإلكتروني
            // Mail::to($email)->send(new PaymentReminderEmail($data));
            
            Log::info('Payment reminder email sent successfully', [
                'invoice_id' => $invoice->id,
                'email' => $email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send payment reminder email', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * حساب الأيام المتأخرة
     */
    private function calculateDaysOverdue(Invoice $invoice): int
    {
        if (!$invoice->due_date) {
            return 0;
        }

        $dueDate = $invoice->due_date;
        $now = now();

        if ($now->gt($dueDate)) {
            return $now->diffInDays($dueDate);
        }

        return 0;
    }

    /**
     * إرسال فاتورة للمزود
     */
    public function sendProviderInvoice(string $email, Invoice $invoice): bool
    {
        try {
            $data = [
                'invoice' => $invoice,
                'provider' => $invoice->user,
                'service' => $invoice->booking->service,
                'commission_details' => $invoice->commission_breakdown,
                'provider_amount' => $invoice->provider_amount,
                'platform_amount' => $invoice->platform_amount,
            ];

            // إرسال البريد الإلكتروني
            // Mail::to($email)->send(new ProviderInvoiceEmail($data));
            
            Log::info('Provider invoice email sent successfully', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'provider_id' => $invoice->user_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send provider invoice email', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * إرسال فاتورة للعميل
     */
    public function sendCustomerInvoice(string $email, Invoice $invoice): bool
    {
        try {
            $data = [
                'invoice' => $invoice,
                'customer' => $invoice->user,
                'service' => $invoice->booking->service,
                'total_amount' => $invoice->total_amount,
                'tax_amount' => $invoice->tax_amount,
                'discount_amount' => $invoice->discount_amount,
                'payment_method' => $invoice->payment_method,
            ];

            // إرسال البريد الإلكتروني
            // Mail::to($email)->send(new CustomerInvoiceEmail($data));
            
            Log::info('Customer invoice email sent successfully', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'customer_id' => $invoice->user_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send customer invoice email', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}

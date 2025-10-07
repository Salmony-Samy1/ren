<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class SendSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $recipient;
    protected string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $recipient, string $message)
    {
        $this->recipient = $recipient;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Use SmsService abstraction; if not configured, log and skip in non-production
        try {
            $service = app(\App\Services\SmsService::class);
            $service->sendSms($this->normalizePhone($this->recipient), $this->message);
        } catch (\Throwable $e) {
            \Log::error('SMS send failed', ['to'=>$this->recipient,'error'=>$e->getMessage()]);
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Normalize to international format without revealing provider; basic rules for SA/BH
        $digits = preg_replace('/[^0-9]/', '', $phone);
        // Detect country by prefix if present, else assume SA if 9 digits starting with 5, BH if 8 digits
        if (str_starts_with($digits, '966')) {
            $nsn = substr($digits, 3);
            return '+966'.ltrim($nsn, '0');
        }
        if (str_starts_with($digits, '973')) {
            $nsn = substr($digits, 3);
            return '+973'.$nsn;
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            return '+966'.$digits;
        }
        if (strlen($digits) === 8) {
            return '+973'.$digits;
        }
        return '+'.$digits; // fallback
    }
}

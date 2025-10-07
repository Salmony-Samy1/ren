<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\RestaurantTable;
use Carbon\Carbon;

class RestaurantAutoReavailabilityCommand extends Command
{
    protected $signature = 'restaurants:auto-reavailability';
    protected $description = 'Mark AUTO restaurant tables available after booking end + buffer';

    public function handle(): int
    {
        $now = Carbon::now();
        // نفترض أن إعادة الإتاحة تتم تلقائياً بمجرّد تجاوز الوقت؛
        // حالياً لا نخزن حالة للطاولة، لأن التحقق يعتمد على الإحصاء اليومي (used vs quantity).
        // لذلك هذه الأوامر هي نقطة ارتكاز مستقبلية لو احتجنا flip حالة للطاولة.
        // لا حاجة لتغيير حالة في DB الآن، لأن منطق الإتاحة يحسبها ديناميكياً.
        $this->info('Auto re-availability check complete (no explicit state flip required).');
        return self::SUCCESS;
    }
}


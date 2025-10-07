<?php

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recompute($review);
    }

    public function updated(Review $review): void
    {
        $this->recompute($review);
    }

    public function deleted(Review $review): void
    {
        $this->recompute($review, true);
    }

    public function restored(Review $review): void
    {
        $this->recompute($review);
    }

    private function recompute(Review $review, bool $deleted = false): void
    {
        $serviceId = $review->service_id;
        // حساب المتوسط للمراجعات المعتمدة فقط وغير المحذوفة
        $avg = DB::table('reviews')
            ->where('service_id', $serviceId)
            ->where('is_approved', 1)
            ->whereNull('deleted_at')
            ->avg('rating');

        DB::table('services')->where('id', $serviceId)->update([
            'rating_avg' => $avg ? round($avg, 2) : 0,
        ]);
    }
}


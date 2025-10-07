<?php

namespace App\Observers;

use App\Models\MainService;
use Illuminate\Support\Facades\Cache;

class MainServiceObserver
{
    protected function flush(): void
    {
        \App\Support\CacheVersion::bump('main_services');
    }

    public function created(MainService $m): void { $this->flush(); }
    public function updated(MainService $m): void { $this->flush(); }
    public function deleted(MainService $m): void { $this->flush(); }
    public function restored(MainService $m): void { $this->flush(); }
}


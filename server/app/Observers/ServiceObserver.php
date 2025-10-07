<?php

namespace App\Observers;

use App\Models\Service;
use Illuminate\Support\Facades\Cache;

class ServiceObserver
{
    protected function flush(): void
    {
        \App\Support\CacheVersion::bump('services');
        \App\Support\CacheVersion::bump('search');
    }

    public function created(Service $service): void { $this->flush(); }
    public function updated(Service $service): void { $this->flush(); }
    public function deleted(Service $service): void { $this->flush(); }
    public function restored(Service $service): void { $this->flush(); }
}


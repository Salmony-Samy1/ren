<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    protected function flush(): void
    {
        \App\Support\CacheVersion::bump('categories');
        \App\Support\CacheVersion::bump('search');
    }

    public function created(Category $category): void { $this->flush(); }
    public function updated(Category $category): void { $this->flush(); }
    public function deleted(Category $category): void { $this->flush(); }
    public function restored(Category $category): void { $this->flush(); }
}


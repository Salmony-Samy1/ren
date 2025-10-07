<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-filter me-2"></i>
            فلترة سجل الدخول
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- البحث -->
            <div class="col-md-4">
                <label for="subSearch" class="form-label">البحث</label>
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           id="subSearch" 
                           wire:model.live.debounce.500ms="search" 
                           placeholder="البحث..."
                           wire:loading.attr="disabled"
                           maxlength="100">
                    <div wire:loading wire:target="search" class="input-group-text">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
            </div>

            <!-- فلتر حالة الدخول -->
            <div class="col-md-3">
                <label for="loginStatus" class="form-label">حالة الدخول</label>
                <select class="form-select" 
                        wire:model.live="loginStatusFilter"
                        wire:loading.attr="disabled"
                        wire:target="loginStatusFilter">
                    <option value="">جميع الحالات</option>
                    <option value="success">نجح</option>
                    <option value="failed">فشل</option>
                </select>
            </div>
            
            <!-- فلتر المنصة -->
            <div class="col-md-3">
                <label for="loginPlatform" class="form-label">المنصة</label>
                <select class="form-select" 
                        wire:model.live="loginPlatformFilter"
                        wire:loading.attr="disabled"
                        wire:target="loginPlatformFilter">
                    <option value="">جميع المنصات</option>
                    <option value="web">ويب</option>
                    <option value="mobile">موبايل</option>
                    <option value="api">API</option>
                </select>
            </div>

            <!-- فلتر التاريخ -->
            <div class="col-md-1">
                <label for="dateFrom" class="form-label">من تاريخ</label>
                <input type="date" 
                       class="form-control" 
                       id="dateFrom" 
                       wire:model.live="dateFrom"
                       wire:loading.attr="disabled"
                       wire:target="dateFrom">
            </div>
            
            <div class="col-md-1">
                <label for="dateTo" class="form-label">إلى تاريخ</label>
                <input type="date" 
                       class="form-control" 
                       id="dateTo" 
                       wire:model.live="dateTo"
                       wire:loading.attr="disabled"
                       wire:target="dateTo">
            </div>

            <!-- أزرار التحكم -->
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group w-100" role="group">
                    <button type="button" 
                            class="btn btn-outline-secondary btn-sm" 
                            wire:click="clearSubFilters"
                            wire:loading.attr="disabled"
                            wire:target="clearSubFilters">
                        <i class="fas fa-times me-1"></i>مسح
                    </button>
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            wire:click="applySubFilters"
                            wire:loading.attr="disabled"
                            wire:target="applySubFilters">
                        <i class="fas fa-search me-1"></i>تطبيق
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div wire:loading wire:target="search,loginStatusFilter,loginPlatformFilter,dateFrom,dateTo" class="text-center mt-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
            <span class="text-muted ms-2">جاري تطبيق الفلاتر...</span>
        </div>
    </div>
</div>
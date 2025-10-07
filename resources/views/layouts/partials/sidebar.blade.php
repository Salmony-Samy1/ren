
<style>
    /*
     * زيادة عرض القائمة الجانبية
     * القيمة الافتراضية غالباً ما تكون 240px أو 250px
     * يمكنك تعديل قيمة 280px حسب ما تراه مناسباً
    */
    .left-sidebar {
        width: 280px;
    }

    /*
     * عند فتح القائمة الجانبية، يجب إزاحة المحتوى الرئيسي بنفس المقدار
     * هذا الكود يفترض أن العنصر الذي يغلف المحتوى الرئيسي لديه كلاس .page-wrapper
     * وهو الكلاس الشائع في مثل هذه القوالب.
    */
    body[data-layout="vertical"][data-sidebartype="full"] .page-wrapper {
        margin-right: 280px; /* For RTL */
        margin-left: auto;
    }

    /*
     * في الشاشات الصغيرة عندما تكون القائمة مخفية، نعيد الهامش إلى الصفر
    */
    @media (max-width: 1199px) {
        body[data-layout="vertical"] .page-wrapper {
            margin-right: 0;
        }
    }
</style>

<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="./index.html" class="text-nowrap logo-img">
                <img src="{{ asset('assets/images/logos/gathro.png') }}" alt="Gathrow Logo" style="height: 100px; width: auto;" />
            </a>
            <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
                <i class="ti ti-x fs-8"></i>
            </div>
        </div>
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
            <!-- Search Bar -->
            <div class="search-box mb-3 px-3">
                <div class="position-relative">
                    <input type="text" class="form-control search-input" placeholder="البحث في القوائم...">
                    <iconify-icon icon="solar:magnifer-linear" class="search-icon"></iconify-icon>
                </div>
            </div>

            <ul id="sidebarnav">
                <!-- الرئيسية -->
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <iconify-icon icon="solar:home-smile-linear"></iconify-icon>
                        <span class="hide-menu">الرئيسية</span>
                    </a>
                </li>


                @php $isUsersActive = request()->is('admin/users*'); @endphp
                <li class="sidebar-item">
                    <a class="sidebar-link {{ $isUsersActive ? 'active' : '' }}" href="{{ route('admin.users.list') }}">
                        <iconify-icon icon="solar:user-linear"></iconify-icon>
                        <span class="hide-menu">معلومات المستخدمين</span>
                    </a>
                </li>

                @php $isProvidersActive = request()->is('admin/providers*'); @endphp
                <li class="sidebar-item">
                    <a class="sidebar-link justify-content-between has-arrow {{ $isProvidersActive ? 'active' : '' }}" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#providers-management" aria-expanded="{{ $isProvidersActive ? 'true' : 'false' }}" aria-controls="providers-management">
                        <iconify-icon icon="solar:heart-linear"></iconify-icon>
                        <span class="hide-menu">إدارة مقدمي الخدمات</span>
                    </a>
                    <ul id="providers-management" aria-expanded="{{ $isProvidersActive ? 'true' : 'false' }}" class="collapse first-level {{ $isProvidersActive ? 'show' : '' }}" data-bs-parent="#sidebarnav">
                        <li class="sidebar-item"><a href="{{ route('admin.providers.list') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.providers.list') ? 'active' : '' }}"><iconify-icon icon="solar:folder-linear"></iconify-icon><span class="hide-menu">قائمة مقدمي الخدمات</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.documents.index') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}"><iconify-icon icon="solar:file-text-linear"></iconify-icon><span class="hide-menu">الوثائق القانونية</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.providers.status.overview') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.providers.status*') ? 'active' : '' }}"><iconify-icon icon="solar:shield-check-linear"></iconify-icon><span class="hide-menu">حالة مقدمي الخدمات</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.providers.profiles.overview') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.providers.profile*') ? 'active' : '' }}"><iconify-icon icon="solar:user-id-linear"></iconify-icon><span class="hide-menu">ملفات مقدمي الخدمات</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.providers.ratings') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.providers.ratings') ? 'active' : '' }}"><iconify-icon icon="solar:star-linear"></iconify-icon><span class="hide-menu">تقييمات العملاء</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.providers.performance') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.providers.performance') ? 'active' : '' }}"><iconify-icon icon="solar:chart-linear"></iconify-icon><span class="hide-menu">تقارير الأداء</span></a></li>
                    </ul>
                </li>


                @php $isEventsActive = request()->is('admin/events*'); @endphp
                <li class="sidebar-item">
                    <a class="sidebar-link justify-content-between has-arrow {{ $isEventsActive ? 'active' : '' }}" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#events-management" aria-expanded="{{ $isEventsActive ? 'true' : 'false' }}" aria-controls="events-management">
                        <iconify-icon icon="solar:calendar-linear"></iconify-icon>
                        <span class="hide-menu">إدارة الفعاليات</span>
                    </a>
                    <ul id="events-management" aria-expanded="{{ $isEventsActive ? 'true' : 'false' }}" class="collapse first-level {{ $isEventsActive ? 'show' : '' }}" data-bs-parent="#sidebarnav">
                        <li class="sidebar-item"><a href="{{ route('admin.events.list') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.events.list') ? 'active' : '' }}"><iconify-icon icon="solar:list-linear"></iconify-icon><span class="hide-menu">قائمة الفعاليات</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.events.content') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.events.content') ? 'active' : '' }}"><iconify-icon icon="solar:gallery-linear"></iconify-icon><span class="hide-menu">إدارة المحتوى</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.events.tickets') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.events.tickets') ? 'active' : '' }}"><iconify-icon icon="solar:ticket-linear"></iconify-icon><span class="hide-menu">تتبع التذاكر</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.events.attendance') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.events.attendance') ? 'active' : '' }}"><iconify-icon icon="solar:users-group-rounded-linear"></iconify-icon><span class="hide-menu">تتبع الحضور</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.events.user-content') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.events.user-content') ? 'active' : '' }}"><iconify-icon icon="solar:chat-round-linear"></iconify-icon><span class="hide-menu">مراجعة المحتوى</span></a></li>
                        <li class="sidebar-item"><a href="{{ route('admin.events.livestream') }}" class="sidebar-link submenu-link {{ request()->routeIs('admin.events.livestream') ? 'active' : '' }}"><iconify-icon icon="solar:videocamera-linear"></iconify-icon><span class="hide-menu">البث المباشر</span></a></li>
                    </ul>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('admin.catering.offers') ? 'active' : '' }}" href="{{ route('admin.catering.offers') }}">
                        <iconify-icon icon="solar:kitchen-holder-linear"></iconify-icon>
                        <span class="hide-menu">إدارة خدمات الكيترينق</span>
                    </a>
                </li>
                <li class="sidebar-item">
                     <a class="sidebar-link" href="#">
                        <iconify-icon icon="solar:heart-linear"></iconify-icon>
                        <span class="hide-menu">إدارة الخدمات والمنتجات</span>
                    </a>
                </li>


                 <li class="sidebar-item">
                    <a class="sidebar-link" href="#">
                        <iconify-icon icon="solar:dollar-minimalistic-linear"></iconify-icon>
                        <span class="hide-menu">إدارة المالية والتحصيل</span>
                    </a>
                </li>
                @php $isAnalyticsActive = request()->is('admin/analytics*'); @endphp
                <li class="sidebar-item">
                    <a class="sidebar-link justify-content-between has-arrow {{ $isAnalyticsActive ? 'active' : '' }}" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#analytics-management" aria-expanded="{{ $isAnalyticsActive ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                        <span class="hide-menu">تحليلات النظام</span>
                    </a>
                    <ul id="analytics-management" aria-expanded="{{ $isAnalyticsActive ? 'true' : 'false' }}" class="collapse first-level {{ $isAnalyticsActive ? 'show' : '' }}" data-bs-parent="#sidebarnav">
                        <li class="sidebar-item"><a href="#" class="sidebar-link submenu-link"><iconify-icon icon="solar:users-group-rounded-linear"></iconify-icon><span class="hide-menu">تحليلات المستخدمين</span></a></li>
                        <li class="sidebar-item"><a href="#" class="sidebar-link submenu-link"><iconify-icon icon="solar:box-linear"></iconify-icon><span class="hide-menu">تحليلات الخدمات</span></a></li>
                    </ul>
                </li>
                
                @php $isSupportActive = request()->is('admin/support*'); @endphp
                <li class="sidebar-item">
                    <a class="sidebar-link justify-content-between has-arrow {{ $isSupportActive ? 'active' : '' }}" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#support-management" aria-expanded="{{ $isSupportActive ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:headphones-round-sound-linear"></iconify-icon>
                        <span class="hide-menu">الدعم الفني</span>
                    </a>
                    <ul id="support-management" aria-expanded="{{ $isSupportActive ? 'true' : 'false' }}" class="collapse first-level {{ $isSupportActive ? 'show' : '' }}" data-bs-parent="#sidebarnav">
                        <li class="sidebar-item"><a href="#" class="sidebar-link submenu-link"><iconify-icon icon="solar:ticket-linear"></iconify-icon><span class="hide-menu">تذاكر الدعم</span></a></li>
                        <li class="sidebar-item"><a href="#" class="sidebar-link submenu-link"><iconify-icon icon="solar:question-circle-linear"></iconify-icon><span class="hide-menu">الأسئلة الشائعة</span></a></li>
                    </ul>
                </li>
                @php $isSettingsActive = request()->is('admin/settings*'); @endphp
                <li class="sidebar-item">
                    <a class="sidebar-link justify-content-between has-arrow {{ $isSettingsActive ? 'active' : '' }}" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#settings-management" aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:settings-linear"></iconify-icon>
                        <span class="hide-menu">الإعدادات</span>
                    </a>
                    <ul id="settings-management" aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}" class="collapse first-level {{ $isSettingsActive ? 'show' : '' }}" data-bs-parent="#sidebarnav">
                        <li class="sidebar-item"><a href="#" class="sidebar-link submenu-link"><iconify-icon icon="solar:settings-linear"></iconify-icon><span class="hide-menu">الإعدادات العامة</span></a></li>
                        <li class="sidebar-item"><a href="#" class="sidebar-link submenu-link"><iconify-icon icon="solar:shield-user-outline"></iconify-icon><span class="hide-menu">الأدوار والصلاحيات</span></a></li>
                    </ul>
                </li>
            </ul>
            <div class="unlimited-access d-flex align-items-center hide-menu bg-primary-subtle position-relative mb-7 mt-4 p-3 rounded-3">
                <div class="flex-shrink-0">
                    <h6 class="fw-semibold fs-4 mb-2 text-dark w-75 lh-sm">لوحة تحكم جاثرو الإدارية</h6>
                    <p class="fs-2 text-muted mb-3">نظام إدارة شامل ومتكامل</p>
                    <a href="#" target="_blank" class="btn btn-primary fs-2 fw-semibold lh-sm">© 2026</a>
                </div>
                <div class="unlimited-access-img">
                    <img src="{{ asset('assets/images/logos/gathro.png') }}" alt="Gathro Logo" class="img-fluid" style="max-height: 60px;">
                </div>
            </div>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>
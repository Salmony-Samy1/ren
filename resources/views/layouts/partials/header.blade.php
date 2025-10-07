<div class="app-topstrip bg-dark py-3 px-4 w-100 d-lg-flex align-items-center justify-content-between">
    <div class="d-none d-sm-flex align-items-center justify-content-center gap-9 mb-3 mb-lg-0">
        <a class="d-flex justify-content-center" >
            <img src="{{asset('assets/images/logos/gathro.png')}}" alt="شعار المشروع" style="height: 40px; width: auto;">
            </a>

        <div class="d-none d-xl-flex align-items-center gap-3 border-start border-white border-opacity-25 ps-9">
            <a  class="link-hover d-flex align-items-center gap-2 border-0 text-white lh-sm fs-4">
                <iconify-icon class="fs-6" icon="solar:home-2-linear"></iconify-icon>
                الرئيسية
            </a>
            <a  class="link-hover d-flex align-items-center gap-2 border-0 text-white lh-sm fs-4">
                <iconify-icon class="fs-6" icon="solar:question-circle-linear"></iconify-icon>
                التعليمات
            </a>
            <a  class="link-hover d-flex align-items-center gap-2 border-0 text-white lh-sm fs-4">
                <iconify-icon class="fs-6" icon="solar:chat-round-dots-linear"></iconify-icon>
                الدعم الفني
            </a>
        </div>
    </div>

    <div class="d-lg-flex align-items-center gap-3">
        <h3 class="text-linear-gradient mb-3 mb-lg-0 fs-3 text-center fw-semibold">مرحباً بك مجدداً</h3>
        <div class="d-sm-flex align-items-center justify-content-center gap-8">
            <div class="d-flex align-items-center justify-content-center gap-8 mb-3 mb-sm-0">
                <div class="dropdown d-flex">
                    <a class="btn live-preview-drop fs-4 lh-sm btn-outline-primary rounded border-white border border-opacity-40 text-white d-flex align-items-center gap-2 px-3 py-2"
                        href="javascript:void(0)" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <iconify-icon class="fs-6" icon="solar:user-circle-linear"></iconify-icon>
                        حسابي
                        <iconify-icon class="fs-6" icon="solar:alt-arrow-down-linear"></iconify-icon>
                    </a>
                    <div class="dropdown-menu p-3 dropdown-menu-end dropdown-menu-animate-up overflow-hidden rounded"
                        aria-labelledby="userProfileDropdown">
                        <div class="message-body">
                            <a  class="dropdown-item rounded fw-normal d-flex align-items-center gap-6">
                                <iconify-icon class="fs-6" icon="solar:user-id-linear"></iconify-icon>
                                الملف الشخصي
                            </a>
                            <a  class="dropdown-item rounded fw-normal d-flex align-items-center gap-6">
                                <iconify-icon class="fs-6" icon="solar:mailbox-linear"></iconify-icon>
                                صندوق الوارد
                            </a>
                            <a   class="dropdown-item rounded fw-normal d-flex align-items-center gap-6">
                                <iconify-icon class="fs-6" icon="solar:settings-linear"></iconify-icon>
                                الإعدادات
                            </a>
                             <hr class="dropdown-divider">
                             <form action="{{ route('logout') }}" method="POST" class="dropdown-item rounded fw-normal d-flex align-items-center gap-6 text-danger admin-logout">
                                @csrf
                                <button type="submit" class="btn border-0 bg-transparent d-flex align-items-center gap-2 text-danger">
                                    <iconify-icon class="fs-6" icon="solar:logout-linear"></iconify-icon>
                                    <p class="mb-0 fs-3">تسجيل الخروج</p>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <a 
                    class="get-pro-btn rounded btn btn-primary d-flex align-items-center gap-2 fs-4 border-0 px-3 py-2">
                    <iconify-icon class="fs-5" icon="solar:add-circle-linear"></iconify-icon>
                    إضافة جديد
                </a>
            </div>
            <a 
                class="all-access-pass-btn rounded btn btn-primary d-flex align-items-center justify-content-center gap-2 fs-4 border-0 text-black px-3 py-2">
                <iconify-icon class="fs-5" icon="solar:bell-linear"></iconify-icon>
                الإشعارات
            </a>
        </div>
    </div>
</div>
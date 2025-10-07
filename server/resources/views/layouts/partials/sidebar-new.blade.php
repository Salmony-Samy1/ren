<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - جاثرو</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== CSS Variables for Consistency ===== */
        :root {
            --sidebar-width: 280px;
            --sidebar-bg: #ffffff;
            --sidebar-text: #374151;
            --sidebar-text-light: #6b7280;
            --sidebar-hover: #f3f4f6;
            --sidebar-active: #8b5cf6;
            --sidebar-active-bg: rgba(139, 92, 246, 0.1);
            --sidebar-border: #e5e7eb;
            --sidebar-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ===== Main Layout ===== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ===== Sidebar Styles ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            box-shadow: var(--sidebar-shadow);
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            transition: var(--transition);
            border-left: 1px solid var(--sidebar-border);
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ===== Sidebar Header ===== */
        .sidebar-header {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid var(--sidebar-border);
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .sidebar-brand:hover {
            color: white;
            text-decoration: none;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* ===== Search Box ===== */
        .sidebar-search {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--sidebar-border);
            border-radius: 8px;
            background: #f8fafc;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            background: white;
        }

        .search-icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--sidebar-text-light);
            font-size: 0.875rem;
        }

        /* ===== Navigation Styles ===== */
        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--sidebar-text-light);
            padding: 0 1.25rem;
            margin-bottom: 0.75rem;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: var(--transition);
            border-radius: 0;
            position: relative;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: var(--sidebar-text);
            text-decoration: none;
        }

        .nav-link.active {
            background: var(--sidebar-active);
            color: white;
            font-weight: 600;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: white;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            margin-left: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .nav-text {
            flex: 1;
        }

        .nav-arrow {
            margin-right: 0.5rem;
            transition: var(--transition);
            font-size: 0.75rem;
        }

        .nav-arrow.expanded {
            transform: rotate(180deg);
        }

        /* ===== Submenu Styles ===== */
        .submenu {
            background: #f8fafc;
            border-right: 3px solid var(--sidebar-active);
            margin-right: 1rem;
            border-radius: 0 8px 8px 0;
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease-out;
        }

        .submenu.show {
            max-height: 500px;
        }

        .submenu-item {
            margin-bottom: 0.125rem;
        }

        .submenu-link {
            display: flex;
            align-items: center;
            padding: 0.625rem 1.25rem 0.625rem 2.5rem;
            color: var(--sidebar-text-light);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.8125rem;
            position: relative;
        }

        .submenu-link:hover {
            background: rgba(139, 92, 246, 0.05);
            color: var(--sidebar-text);
            text-decoration: none;
        }

        .submenu-link.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active);
            font-weight: 600;
        }

        .submenu-link.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--sidebar-active);
        }

        .submenu-icon {
            width: 16px;
            height: 16px;
            margin-left: 0.5rem;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        /* ===== Mobile Styles ===== */
        .mobile-header {
            display: none;
            padding: 1rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
        }

        .mobile-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--sidebar-text);
            padding: 0.5rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .mobile-toggle:hover {
            background: var(--sidebar-hover);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* ===== Main Content ===== */
        .main-content {
            flex: 1;
            margin-right: var(--sidebar-width);
            padding: 2rem;
            transition: var(--transition);
        }

        .content-header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .content-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--sidebar-text);
            margin: 0;
        }

        .content-subtitle {
            color: var(--sidebar-text-light);
            margin-top: 0.5rem;
        }

        /* ===== Responsive Design ===== */
        @media (max-width: 768px) {
            .mobile-header {
                display: block;
            }

            .sidebar {
                transform: translateX(100%);
                width: 100%;
                max-width: 320px;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-right: 0;
                padding: 1rem;
                padding-top: 5rem;
            }

            .content-header {
                padding: 1rem;
            }

            .content-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                max-width: 100%;
            }

            .main-content {
                padding: 0.75rem;
                padding-top: 4.5rem;
            }
        }

        /* ===== Animation Classes ===== */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        /* ===== Accessibility ===== */
        .nav-link:focus,
        .submenu-link:focus {
            outline: 2px solid var(--sidebar-active);
            outline-offset: 2px;
        }

        /* ===== Print Styles ===== */
        @media print {
            .sidebar,
            .mobile-header,
            .sidebar-overlay {
                display: none !important;
            }

            .main-content {
                margin-right: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex align-items-center justify-content-between">
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 fw-bold text-primary">جاثرو</h5>
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-bell text-muted"></i>
                <div class="dropdown">
                    <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                        <img src="https://via.placeholder.com/32x32/8b5cf6/ffffff?text=U" 
                             class="rounded-circle" width="32" height="32" alt="User">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>الملف الشخصي</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>الإعدادات</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <div class="brand-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <span>جاثرو</span>
            </a>
        </div>

        <!-- Search Box -->
        <div class="sidebar-search">
            <div class="position-relative">
                <input type="text" class="search-input" placeholder="البحث في القوائم...">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>

        <!-- Navigation -->
        <div class="sidebar-nav">
            <!-- الرئيسية -->
            <div class="nav-section">
                <div class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">الرئيسية</span>
                    </a>
                </div>
            </div>

            <!-- إدارة المستخدمين -->
            <div class="nav-section">
                <div class="nav-section-title">إدارة المستخدمين والخدمات</div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#usersMenu" aria-expanded="true">
                        <i class="fas fa-users nav-icon"></i>
                        <span class="nav-text">عرض معلومات المستخدمين</span>
                        <i class="fas fa-chevron-down nav-arrow expanded"></i>
                    </a>
                    <div class="submenu show" id="usersMenu">
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-user submenu-icon"></i>
                                <span>عرض معلومات الحساب</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link active">
                                <i class="fas fa-clock submenu-icon"></i>
                                <span>تتبع سجل الدخول</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-shield-alt submenu-icon"></i>
                                <span>التحكم بالحساب</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-users-cog submenu-icon"></i>
                                <span>تصنيف المستخدمين</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-exclamation-triangle submenu-icon"></i>
                                <span>لوحة تحذيرات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-bell submenu-icon"></i>
                                <span>إرسال إشعارات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-chart-line submenu-icon"></i>
                                <span>تتبع استخدام الخدمات</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#providersMenu">
                        <i class="fas fa-heart nav-icon"></i>
                        <span class="nav-text">إدارة مقدمي الخدمات</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </a>
                    <div class="submenu" id="providersMenu">
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-folder submenu-icon"></i>
                                <span>ملفات مقدمي الخدمات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-star submenu-icon"></i>
                                <span>تقييمات العملاء</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-chart-bar submenu-icon"></i>
                                <span>تقارير الأداء</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-medal submenu-icon"></i>
                                <span>التحفيز والتعليق</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-bell submenu-icon"></i>
                                <span>التذكيرات التلقائية</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-chart-pie submenu-icon"></i>
                                <span>مقارنة الأداء</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إدارة الخدمات والفعاليات -->
            <div class="nav-section">
                <div class="nav-section-title">إدارة الخدمات والفعاليات</div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#eventsMenu">
                        <i class="fas fa-calendar nav-icon"></i>
                        <span class="nav-text">إدارة الفعاليات</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </a>
                    <div class="submenu" id="eventsMenu">
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-calendar-plus submenu-icon"></i>
                                <span>إنشاء وتنظيم الفعاليات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-file-alt submenu-icon"></i>
                                <span>التحكم في المحتوى</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-ticket-alt submenu-icon"></i>
                                <span>تتبع التذاكر</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-users submenu-icon"></i>
                                <span>إدارة الحضور</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-eye submenu-icon"></i>
                                <span>مراجعة محتوى المستخدمين</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-play-circle submenu-icon"></i>
                                <span>إدارة البث المباشر</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#financeMenu">
                        <i class="fas fa-dollar-sign nav-icon"></i>
                        <span class="nav-text">إدارة المالية والتحصيل</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </a>
                    <div class="submenu" id="financeMenu">
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-chart-line submenu-icon"></i>
                                <span>عرض الإيرادات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-calculator submenu-icon"></i>
                                <span>حساب صافي الربح</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-file-invoice submenu-icon"></i>
                                <span>التقارير المالية</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-percentage submenu-icon"></i>
                                <span>إدارة العمولات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-receipt submenu-icon"></i>
                                <span>إدارة الضرائب</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-tags submenu-icon"></i>
                                <span>الخصومات والكوبونات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-bell submenu-icon"></i>
                                <span>التنبيهات المالية</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#servicesMenu">
                        <i class="fas fa-heart nav-icon"></i>
                        <span class="nav-text">إدارة الخدمات والمنتجات</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </a>
                    <div class="submenu" id="servicesMenu">
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-cogs submenu-icon"></i>
                                <span>إدارة الخدمات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-box submenu-icon"></i>
                                <span>إدارة المنتجات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-dollar-sign submenu-icon"></i>
                                <span>التسعير</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-check-circle submenu-icon"></i>
                                <span>التوفر</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#cateringMenu">
                        <i class="fas fa-utensils nav-icon"></i>
                        <span class="nav-text">إدارة خدمات الكيترينق</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </a>
                    <div class="submenu" id="cateringMenu">
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-tag submenu-icon"></i>
                                <span>تصنيف العروض</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-list submenu-icon"></i>
                                <span>إدارة القوائم</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-clipboard-list submenu-icon"></i>
                                <span>إدارة الطلبات</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-boxes submenu-icon"></i>
                                <span>التحكم في المخزون</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-clock submenu-icon"></i>
                                <span>جدولة التوصيل</span>
                            </a>
                        </div>
                        <div class="submenu-item">
                            <a href="#" class="submenu-link">
                                <i class="fas fa-truck submenu-icon"></i>
                                <span>إدارة الموردين</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- التقارير والتحليلات -->
            <div class="nav-section">
                <div class="nav-section-title">التقارير والتحليلات</div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span class="nav-text">التقارير الشاملة</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-filter nav-icon"></i>
                        <span class="nav-text">المرشحات المتقدمة</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-download nav-icon"></i>
                        <span class="nav-text">تصدير التقارير</span>
                    </a>
                </div>
            </div>

            <!-- الإعدادات -->
            <div class="nav-section">
                <div class="nav-section-title">الإعدادات والنظام</div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-cog nav-icon"></i>
                        <span class="nav-text">إعدادات النظام</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-shield-alt nav-icon"></i>
                        <span class="nav-text">الأمان والصلاحيات</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-headset nav-icon"></i>
                        <span class="nav-text">الدعم الفني</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header fade-in">
            <h1 class="content-title">مرحباً بك في لوحة التحكم</h1>
            <p class="content-subtitle">إدارة شاملة ومنظمة لجميع خدمات جاثرو</p>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">محتوى الصفحة الرئيسية</h5>
                        <p class="card-text">هذا مثال على المحتوى الرئيسي للصفحة. يمكنك إضافة أي محتوى تريده هنا.</p>
                        <a href="#" class="btn btn-primary">بدء الاستخدام</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ===== Sidebar JavaScript =====
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const navLinks = document.querySelectorAll('.nav-link[data-bs-toggle="collapse"]');
            const submenus = document.querySelectorAll('.submenu');

            // ===== Mobile Sidebar Toggle =====
            function toggleSidebar() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
                
                // Prevent body scroll when sidebar is open
                if (sidebar.classList.contains('show')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }

            // ===== Event Listeners =====
            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);

            // ===== Submenu Toggle =====
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('data-bs-target');
                    const targetSubmenu = document.querySelector(targetId);
                    const arrow = this.querySelector('.nav-arrow');
                    
                    // Close all other submenus
                    submenus.forEach(submenu => {
                        if (submenu !== targetSubmenu) {
                            submenu.classList.remove('show');
                            const otherArrow = submenu.previousElementSibling.querySelector('.nav-arrow');
                            if (otherArrow) {
                                otherArrow.classList.remove('expanded');
                            }
                        }
                    });
                    
                    // Toggle current submenu
                    if (targetSubmenu) {
                        targetSubmenu.classList.toggle('show');
                        arrow.classList.toggle('expanded');
                    }
                });
            });

            // ===== Active Link Management =====
            function setActiveLink(clickedLink) {
                // Remove active class from all nav links
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Add active class to clicked link
                clickedLink.classList.add('active');
            }

            // ===== Submenu Active Link Management =====
            function setActiveSubmenuLink(clickedLink) {
                // Remove active class from all submenu links
                document.querySelectorAll('.submenu-link').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Add active class to clicked link
                clickedLink.classList.add('active');
            }

            // ===== Event Listeners for Active States =====
            document.querySelectorAll('.nav-link:not([data-bs-toggle="collapse"])').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    setActiveLink(this);
                });
            });

            document.querySelectorAll('.submenu-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    setActiveSubmenuLink(this);
                });
            });

            // ===== Search Functionality =====
            const searchInput = document.querySelector('.search-input');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const navItems = document.querySelectorAll('.nav-item');
                
                navItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = searchTerm ? 'none' : 'block';
                    }
                });
            });

            // ===== Keyboard Navigation =====
            document.addEventListener('keydown', function(e) {
                // Close sidebar with Escape key
                if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                    toggleSidebar();
                }
            });

            // ===== Smooth Scrolling =====
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // ===== Resize Handler =====
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });

            // ===== Initialize Active States =====
            // Set initial active states based on current page
            const currentPath = window.location.pathname;
            const activeLink = document.querySelector(`a[href="${currentPath}"]`);
            if (activeLink) {
                if (activeLink.classList.contains('submenu-link')) {
                    setActiveSubmenuLink(activeLink);
                    // Open parent submenu
                    const parentSubmenu = activeLink.closest('.submenu');
                    if (parentSubmenu) {
                        parentSubmenu.classList.add('show');
                        const parentArrow = parentSubmenu.previousElementSibling.querySelector('.nav-arrow');
                        if (parentArrow) {
                            parentArrow.classList.add('expanded');
                        }
                    }
                } else {
                    setActiveLink(activeLink);
                }
            }

            // ===== Animation on Load =====
            setTimeout(() => {
                document.querySelectorAll('.nav-item').forEach((item, index) => {
                    item.style.animationDelay = `${index * 0.1}s`;
                    item.classList.add('fade-in');
                });
            }, 100);
        });

        // ===== Utility Functions =====
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; left: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }

        // ===== Export Functions for External Use =====
        window.SidebarManager = {
            toggle: () => {
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            },
            show: () => {
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
            },
            hide: () => {
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        };
    </script>
</body>
</html>


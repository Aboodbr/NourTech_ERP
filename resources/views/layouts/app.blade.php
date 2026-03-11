<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'NourTech ERP') - AMC</title>

<link rel="icon" type="image/jpeg" href="{{ asset('logo.JPG') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />

   <style>
    :root {
        --amc-red: #D32F2F;
        --amc-blue: #1565C0;
        --amc-orange: #FF8F00;
        --amc-dark: #1e272e;
        --bs-primary: var(--amc-red);
        --bs-primary-rgb: 211, 47, 47;
        --bs-body-font-family: 'Cairo', sans-serif;
    }

    body {
        background-color: #f8f9fa;
        font-family: 'Cairo', sans-serif;
        overflow-x: hidden;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* --- تنسيق الـ Wrapper والسايدبار (متجاوب) --- */
    #wrapper {
        display: flex;
        width: 100%;
        align-items: stretch;
    }

    #sidebar-wrapper {
        min-width: 260px;
        max-width: 260px;
        background: linear-gradient(180deg, var(--amc-dark) 0%, #000000 100%);
        border-left: 3px solid var(--amc-red) !important;
        transition: margin 0.3s ease-in-out;
        min-height: 100vh;
        z-index: 1000;
    }

    /* تأثيرات أزرار السايدبار */
    #sidebar-wrapper .list-group-item {
        background: transparent;
        color: #cfd8dc;
        border: none;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.2s ease;
        margin-bottom: 4px;
    }
    #sidebar-wrapper .list-group-item:hover {
        color: var(--amc-orange);
        background: rgba(255,255,255,0.03);
        padding-right: 25px; /* حركة ناعمة عند الـ Hover */
    }
    #sidebar-wrapper .list-group-item.active {
        color: #fff;
        background: var(--amc-red);
        border-radius: 50px 0 0 50px;
        margin-right: 15px;
        box-shadow: -3px 3px 10px rgba(211, 47, 47, 0.4);
    }
    #sidebar-wrapper .list-group-item i {
        width: 25px;
        color: var(--amc-orange);
        transition: color 0.2s;
    }
    #sidebar-wrapper .list-group-item.active i { color: #fff; }

    /* --- المحتوى الرئيسي --- */
    #page-content-wrapper {
        min-width: 0; /* مهم جداً لمنع انهيار الـ Flexbox في الجداول الكبيرة */
        width: 100%;
        transition: all 0.3s ease-in-out;
    }
    
    .navbar {
        background: #fff !important;
        border-bottom: 3px solid var(--amc-blue);
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }

    .card {
        border: none;
        border-top: 3px solid var(--amc-blue);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    }

    /* --- منطق التجاوب Responsive Logic --- */
    /* في الشاشات الكبيرة: السايدبار ظاهر، وزر التبديل يخفيه */
    @media (min-width: 769px) {
        #wrapper.toggled #sidebar-wrapper {
            margin-right: -260px;
        }
    }

    /* في الشاشات الصغيرة: السايدبار مخفي افتراضياً، وزر التبديل يظهره */
    @media (max-width: 768px) {
        #sidebar-wrapper {
            margin-right: -260px;
            position: fixed; /* لجعله يطفو فوق المحتوى */
            height: 100vh;
            overflow-y: auto;
        }
        #wrapper.toggled #sidebar-wrapper {
            margin-right: 0;
        }
        .navbar-title {
            font-size: 1rem; /* تصغير عنوان الصفحة في الموبايل */
        }
        .back-btn-text {
            display: none; /* إخفاء كلمة "رجوع" في الموبايل وترك السهم فقط */
        }
    }

    /* --- الوضع الليلي Dark Mode --- */
    [data-bs-theme="dark"] body { background-color: #121212 !important; color: #e0e0e0 !important; }
    [data-bs-theme="dark"] .bg-white, [data-bs-theme="dark"] .card { background-color: #1e1e1e !important; border-color: #333 !important; }
    [data-bs-theme="dark"] .navbar { background-color: #1e1e1e !important; border-bottom-color: var(--amc-orange) !important; }
    [data-bs-theme="dark"] .table { color: #e0e0e0 !important; border-color: #444 !important; }
    [data-bs-theme="dark"] .table thead th { background-color: #2c2c2c !important; color: #fff !important; border-bottom: 2px solid #444; }
    [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select { background-color: #2b2b2b !important; border-color: #444 !important; color: #fff !important; }
    
    @media print {
        .sidebar, .navbar, .no-print, form, .btn, .dropdown { display: none !important; }
        #wrapper, #page-content-wrapper { margin: 0; padding: 0; width: 100%; }
        .card { border: none !important; box-shadow: none !important; border-top: none !important; }
        body { background-color: white !important; color: black !important; }
    }
   </style>
    @stack('css')
</head>
<body>

    <div id="wrapper">
        
        @include('includes.sidebar')

        <div id="page-content-wrapper">
            
            <nav class="navbar navbar-expand-lg px-3 px-md-4 py-3">
                <div class="d-flex align-items-center justify-content-between w-100 gap-2">

                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-secondary border-0 shadow-sm" id="menu-toggle" style="background: rgba(0,0,0,0.03);">
                            <i class="fas fa-bars fs-5"></i>
                        </button>
                        <h5 class="mb-0 fw-bold text-secondary navbar-title text-truncate">
                            @yield('title')
                        </h5>
                    </div>

                    <div class="d-flex align-items-center gap-2 gap-md-3">
                        
                        <a href="{{ url()->previous() }}" class="btn btn-secondary shadow-sm fw-bold d-flex align-items-center gap-2">
                            <i class="fa-solid fa-arrow-right-long"></i>
                            <span class="back-btn-text">رجوع</span>
                        </a>

                        <button class="btn btn-light shadow-sm rounded-circle border d-flex justify-content-center align-items-center" 
                                id="theme-toggle" 
                                style="width: 42px; height: 42px; transition: 0.3s;">
                            <i class="fas fa-moon text-secondary fs-5" id="theme-icon"></i>
                        </button>
                    </div>

                </div>
            </nav>

            <div class="container-fluid px-3 px-md-4 py-4">
                @yield('content')
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // --- التحكم في القائمة الجانبية ---
        const wrapper = document.getElementById("wrapper");
        const toggleButton = document.getElementById("menu-toggle");

        toggleButton.onclick = function (e) {
            e.preventDefault();
            wrapper.classList.toggle("toggled");
        };

        // إغلاق السايدبار عند الضغط في أي مكان فارغ (للموبايل فقط)
        document.getElementById("page-content-wrapper").addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && wrapper.classList.contains("toggled")) {
                wrapper.classList.remove("toggled");
            }
        });

        // --- إعدادات Select2 ---
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                dir: "rtl",
                width: '100%'
            });
        });

        // --- الوضع الليلي ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;

        const savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);

        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            applyTheme(newTheme);
        });

        function applyTheme(theme) {
            htmlElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun text-warning fs-5';
                themeToggleBtn.className = 'btn btn-dark shadow-sm rounded-circle border d-flex justify-content-center align-items-center';
            } else {
                themeIcon.className = 'fas fa-moon text-secondary fs-5';
                themeToggleBtn.className = 'btn btn-light shadow-sm rounded-circle border d-flex justify-content-center align-items-center';
            }
        }
    </script>
    @stack('js')
</body>
</html>
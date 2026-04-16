<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'طباعة مستند')</title>
    <style>
        /* 1. إعدادات الصفحة A4 */
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #eee;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 2. حاوية الورقة (The Paper) */
        .page {
            width: 210mm;
            min-height: 297mm; /* ارتفاع A4 */
            padding: 15mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column; /* لضبط الفوتر في الأسفل */
            box-sizing: border-box; /* لحساب الهوامش بدقة */
        }

        /* 3. العلامة المائية (اللوجو الشفاف) */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            opacity: 0.05; /* شفاف جداً */
            z-index: 0;
            pointer-events: none;
        }

        /* 4. الهيدر (الترويسة) */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        .header-logo img {
            height: 100px; /* حجم ثابت للوجو */
            width: auto;
        }
        .header-text {
            text-align: center;
            flex-grow: 1;
        }
        .header-text h1 { margin: 0; color: #cc0000; font-size: 24px; } /* لون أحمر مثل اللوجو */
        .header-text h2 { margin: 5px 0; color: #333; font-size: 18px; }
        .header-text p { margin: 0; color: #777; font-size: 12px; }

        /* 5. المحتوى المتغير */
        .content-wrap {
            flex: 1; /* يأخذ المساحة المتبقية ليدفع الفوتر للأسفل */
            position: relative;
            z-index: 2;
        }

        /* تنسيقات الجداول العامة */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 14px; }
        th { background-color: #333; color: white; }
        
        /* 6. الفوتر (التذييل) - تم إصلاح التداخل */
        .footer-section {
            margin-top: auto; /* يدفعه للأسفل دائماً */
            text-align: center;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            position: relative;
            z-index: 2;
        }

        /* إخفاء أزرار الطباعة عند الطباعة الفعلية */
        @media print {
            body { background: none; }
            .page { margin: 0; box-shadow: none; border: none; width: 100%; height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; padding: 15px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #333; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            🖨️ طباعة المستند
        </button>
    </div>

    <div class="page">
        <img src="{{ asset('logo.jpeg') }}" class="watermark" alt="Watermark">

        <div class="header-section">
            <div class="header-text">
                <h1>المصنعون العرب (AMC)</h1>
                <h2>@yield('header-title', 'نظام إدارة المصنع')</h2>
                <p>NourTech ERP System</p>
            </div>
            <div class="header-logo">
                <img src="{{ asset('logo.jpeg') }}" alt="Logo">
            </div>
        </div>

        <div class="content-wrap">
            @yield('content')
        </div>

        <div class="footer-section">
            <p>المصنعون العرب (AMC)</p>
            <p>تم استخراج هذا المستند إلكترونياً بتاريخ: {{ date('Y-m-d H:i') }}</p>
        </div>
    </div>

</body>
</html>
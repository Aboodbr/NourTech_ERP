<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Cairo', sans-serif; margin: 0; padding: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #D32F2F; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #1565C0; font-size: 24px; }
        .header h3 { margin: 5px 0; color: #333; font-size: 18px; }
        .header p { margin: 5px 0 0 0; color: #555; font-size: 12px; }
        
        /* تنسيقات الجدول الأساسية */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        th { background-color: #f0f0f0; color: #333; font-weight: bold; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #777; border-top: 1px dashed #ccc; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>المصنعون العرب للأجهزة المنزلية (AMC)</h2>
        <h3>{{ $title ?? 'تقرير' }}</h3>
        <p>تاريخ الاستخراج: {{ date('Y-m-d H:i') }}</p>
    </div>

    {!! $table ?? '<p style="text-align:center;">لا توجد بيانات</p>' !!}

    <div class="footer">
        مستخرج من نظام NourTech ERP
    </div>
</body>
</html>
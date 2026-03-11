@extends('layouts.print')

@section('title', 'فاتورة مشتريات #' . $invoice->invoice_number)
@section('header-title', 'فاتورة مشتريات (وارد)')

@section('content')

    <table style="border: none; margin-bottom: 20px;">
        <tr style="border: none;">
            <td style="border: none; text-align: right; width: 50%; vertical-align: top;">
                <h3 style="margin: 0 0 5px 0; color: #333; border-bottom: 2px solid #ddd; display: inline-block; padding-bottom: 3px;">
                    بيانات المورد:
                </h3>
                <div style="margin-top: 5px;"><strong>الاسم:</strong> {{ $invoice->supplier->name }}</div>
                <div><strong>الهاتف:</strong> {{ $invoice->supplier->phone ?? '-' }}</div>
                <div><strong>العنوان:</strong> {{ $invoice->supplier->address ?? '-' }}</div>
            </td>
            
            <td style="border: none; text-align: left; width: 50%; vertical-align: top;">
                <h3 style="margin: 0 0 5px 0; color: #333; border-bottom: 2px solid #ddd; display: inline-block; padding-bottom: 3px;">
                    تفاصيل الفاتورة:
                </h3>
                <div style="margin-top: 5px;"><strong>رقم الفاتورة:</strong> #{{ $invoice->invoice_number }}</div>
                <div><strong>تاريخ الفاتورة:</strong> {{ $invoice->invoice_date }}</div>
                <div><strong>المخزن المستلم:</strong> {{ $invoice->warehouse->name }}</div>
                <div><strong>حالة الفاتورة:</strong> {{ $invoice->status == 'approved' ? 'مرحلة' : 'مسودة' }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th width="5%" style="padding: 10px; border: 1px solid #ddd;">#</th>
                <th width="40%" style="padding: 10px; border: 1px solid #ddd; text-align: right;">الخامة / المنتج</th>
                <th width="15%" style="padding: 10px; border: 1px solid #ddd;">الكمية</th>
                <th width="15%" style="padding: 10px; border: 1px solid #ddd;">سعر الوحدة</th>
                <th width="25%" style="padding: 10px; border: 1px solid #ddd;">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">{{ $loop->iteration }}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">{{ $item->product->name }}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">{{ $item->quantity }}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">{{ number_format($item->unit_price, 2) }}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;">
                    {{ number_format($item->total_price, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
        
        <div style="width: 60%;">
            @if($invoice->notes)
                <div style="border: 1px solid #eee; padding: 10px; background-color: #fcfcfc; border-radius: 5px;">
                    <strong style="color: #666;">ملاحظات:</strong>
                    <p style="margin: 5px 0 0 0; font-size: 13px;">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>

        <div style="width: 35%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background-color: #333; color: white;">
                    <td style="padding: 10px; border: 1px solid #333; text-align: right;">الإجمالي النهائي</td>
                    <td style="padding: 10px; border: 1px solid #333; text-align: center; font-size: 18px; font-weight: bold;">
                        {{ number_format($invoice->total_amount, 2) }} <span style="font-size: 12px;">ج.م</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

@endsection
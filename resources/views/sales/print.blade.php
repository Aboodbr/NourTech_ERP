@extends('layouts.print')

@section('title', 'فاتورة مبيعات #' . $invoice->invoice_number)
@section('header-title', 'فاتورة مبيعات')

@section('content')

    <table style="border: none; margin-bottom: 20px;">
        <tr style="border: none;">
            <td style="border: none; text-align: right; width: 50%; vertical-align: top;">
                <h3 style="margin: 0 0 5px 0; color: #333;">بيانات العميل:</h3>
                <div><strong>الاسم:</strong> {{ $invoice->customer->name }}</div>
                <div><strong>الهاتف:</strong> {{ $invoice->customer->phone ?? '-' }}</div>
                <div><strong>العنوان:</strong> {{ $invoice->customer->address ?? '-' }}</div>
            </td>
            <td style="border: none; text-align: left; width: 50%; vertical-align: top;">
                <h3 style="margin: 0 0 5px 0; color: #333;">بيانات الفاتورة:</h3>
                <div><strong>رقم الفاتورة:</strong> #{{ $invoice->invoice_number }}</div>
                <div><strong>التاريخ:</strong> {{ $invoice->invoice_date }}</div>
                <div><strong>المخزن:</strong> {{ $invoice->warehouse->name }}</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="45%">الصنف</th>
                <th width="15%">الكمية</th>
                <th width="15%">السعر</th>
                <th width="20%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ $item->product->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
        <div style="width: 60%;">
            @if($invoice->notes)
                <div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9; border-radius: 5px;">
                    <strong>ملاحظات:</strong><br>
                    {{ $invoice->notes }}
                </div>
            @endif
        </div>
        <div style="width: 35%;">
            <table style="margin: 0;">
                <tr style="background-color: #333; color: white;">
                    <td style="border: 1px solid #333;"><strong>الإجمالي النهائي</strong></td>
                    <td style="border: 1px solid #333; font-size: 16px;"><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

@endsection
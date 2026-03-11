<table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 14px;">
    <thead>
        <tr style="background-color: #f0f0f0;">
            <th style="border: 1px solid #333; padding: 8px;">رقم الفاتورة</th>
            <th style="border: 1px solid #333; padding: 8px;">المورد</th>
            <th style="border: 1px solid #333; padding: 8px;">المخزن المستلم</th>
            <th style="border: 1px solid #333; padding: 8px; color: #D32F2F;">الإجمالي</th>
            <th style="border: 1px solid #333; padding: 8px;">التاريخ</th>
            <th style="border: 1px solid #333; padding: 8px;">الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $inv)
        <tr>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->invoice_number }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->supplier->name ?? 'غير محدد' }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->warehouse->name ?? '-' }}</td>
            <td style='border: 1px solid #333; padding: 8px; font-weight: bold;'>{{ number_format($inv->total_amount, 2) }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->invoice_date }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->status == 'approved' ? 'مرحلة' : 'مسودة' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
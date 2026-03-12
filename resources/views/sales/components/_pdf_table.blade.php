<table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 14px;">
    <thead>
        <tr class="table-secondary">
            <th style="border: 1px solid #333; padding: 8px;">رقم الفاتورة</th>
            <th style="border: 1px solid #333; padding: 8px;">العميل</th>
            <th style="border: 1px solid #333; padding: 8px;">المخزن الصادر منه</th>
            <th style="border: 1px solid #333; padding: 8px; color: green;">الإجمالي</th>
            <th style="border: 1px solid #333; padding: 8px;">التاريخ</th>
            <th style="border: 1px solid #333; padding: 8px;">الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $inv)
        <tr>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->invoice_number }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->customer->name ?? 'غير محدد' }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->warehouse->name ?? '-' }}</td>
            <td style='border: 1px solid #333; padding: 8px; font-weight: bold;'>{{ number_format($inv->total_amount, 2) }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->invoice_date }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $inv->status == 'approved' ? 'مرحلة (نهائية)' : 'مسودة' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
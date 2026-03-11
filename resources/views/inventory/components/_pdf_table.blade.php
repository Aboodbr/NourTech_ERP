<table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 14px;">
    <thead>
        <tr style="background-color: #f0f0f0;">
            <th style="border: 1px solid #333; padding: 8px;">الكود (SKU)</th>
            <th style="border: 1px solid #333; padding: 8px;">اسم الصنف</th>
            <th style="border: 1px solid #333; padding: 8px;">النوع</th>
            <th style="border: 1px solid #333; padding: 8px;">الرصيد الكلي</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inventory as $item)
        @php
            $stock = $item->stocks_sum_quantity ?? 0;
            $stockColor = $stock <= 0 ? 'color: red;' : 'color: green;';
            $type = optional($item->type)->value == 'raw_material' ? 'مادة خام' : 'منتج تام';
        @endphp
        <tr>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $item->sku }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $item->name }}</td>
            <td style='border: 1px solid #333; padding: 8px;'>{{ $type }}</td>
            <td style='border: 1px solid #333; padding: 8px; font-weight: bold; {{ $stockColor }}' dir="ltr">
                {{ number_format($stock, 1) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
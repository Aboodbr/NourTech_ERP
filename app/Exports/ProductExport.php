<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return $this->products;
    }

    public function headings(): array
    {
        return ['كود الصنف', 'اسم الصنف', 'النوع', 'سعر الشراء', 'سعر البيع', 'إجمالي الرصيد', 'حد الطلب'];
    }

    public function map($product): array
    {
        return [
            $product->sku,
            $product->name,
            $product->type->value ?? 'غير محدد',
            $product->purchase_price,
            $product->selling_price,
            $product->stocks->sum('quantity'),
            $product->min_stock,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

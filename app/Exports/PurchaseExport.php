<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        return $this->invoices;
    }

    // 1. عناوين الأعمدة
    public function headings(): array
    {
        return [
            'رقم الفاتورة',
            'المورد',
            'المخزن المستلم',
            'الإجمالي',
            'التاريخ',
            'الحالة',
        ];
    }

    // 2. ترتيب البيانات
    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->supplier->name ?? '-',
            $invoice->warehouse->name ?? '-',
            $invoice->total_amount,
            $invoice->invoice_date,
            $invoice->status == 'approved' ? 'مرحلة' : 'مسودة',
        ];
    }

    // 3. تنسيق الجدول
    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $invoices;

    // نستقبل البيانات من الكنترولر
    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    // نمرر البيانات لمكتبة الإكسيل
    public function collection()
    {
        return $this->invoices;
    }

    // 1. عناوين الأعمدة في الإكسيل
    public function headings(): array
    {
        return [
            'رقم الفاتورة',
            'العميل',
            'المخزن',
            'الإجمالي',
            'التاريخ',
            'الحالة',
        ];
    }

    // 2. ترتيب البيانات تحت العناوين
    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->customer->name ?? '-',
            $invoice->warehouse->name ?? '-',
            $invoice->total_amount,
            $invoice->invoice_date,
            $invoice->status == 'approved' ? 'مرحلة' : 'مسودة',
        ];
    }

    // 3. تنسيق ملف الإكسيل (جعله من اليمين لليسار وخط عريض للعناوين)
    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true); // مهم جداً للغة العربية
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        // يمكنك ضبط عرض الأعمدة تلقائياً (اختياري)
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

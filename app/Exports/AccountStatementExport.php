<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountStatementExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $statement;

    protected $balance = 0;

    public function __construct($statement)
    {
        $this->statement = $statement;
    }

    public function collection()
    {
        return $this->statement;
    }

    // عناوين الأعمدة
    public function headings(): array
    {
        return [
            'التاريخ',
            'البيان',
            'رقم المستند',
            'مدين (عليه)',
            'دائن (له)',
            'الرصيد التراكمي',
        ];
    }

    // تخطيط البيانات في الصفوف
    public function map($row): array
    {
        // حساب الرصيد التراكمي تماماً كما في الواجهة
        // نفترض هنا عميل (مدين - دائن)، يجب تمرير النوع إذا كان مورد
        $this->balance += ($row['debit'] - $row['credit']);

        return [
            $row['date'],
            $row['notes'],
            $row['ref'],
            $row['debit'] > 0 ? $row['debit'] : '-',
            $row['credit'] > 0 ? $row['credit'] : '-',
            $this->balance,
        ];
    }

    // تلوين وتنسيق الخطوط في الإكسيل
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true); // العناوين بخط عريض
        $sheet->setRightToLeft(true); // جعل الإكسيل من اليمين لليسار
    }
}

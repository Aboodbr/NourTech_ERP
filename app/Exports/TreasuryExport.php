<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TreasuryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return ['التاريخ', 'نوع الحركة', 'المبلغ', 'البيان', 'المرجع'];
    }

    public function map($trx): array
    {
        return [
            $trx->transaction_date,
            $trx->type == 'income' ? 'إيداع/مقبوضات' : 'صرف/مدفوعات',
            $trx->amount,
            $trx->description,
            $trx->reference_id ? class_basename($trx->reference_type).' #'.$trx->reference_id : 'سند يدوي',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

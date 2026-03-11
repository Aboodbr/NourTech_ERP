<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        return ['التاريخ', 'نوع الحركة', 'المستند', 'وارد (+)', 'منصرف (-)', 'المخزن'];
    }

    public function map($trx): array
    {
        $in = $trx->quantity > 0 ? $trx->quantity : '-';
        $out = $trx->quantity < 0 ? abs($trx->quantity) : '-';

        return [
            $trx->created_at->format('Y-m-d H:i'),
            $trx->type->value ?? 'غير محدد',
            $trx->reference ? class_basename($trx->reference_type).' #'.$trx->reference_id : 'يدوي',
            $in,
            $out,
            $trx->stock->warehouse->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}

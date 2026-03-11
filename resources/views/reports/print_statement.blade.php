@extends('layouts.print')

@section('title', 'كشف حساب: ' . $partner->name)
@section('header-title', 'كشف حساب ' . ($type == 'customer' ? 'عميل' : 'مورد'))

@section('content')

    <table style="border: none; margin-bottom: 20px;">
        <tr style="border: none;">
            <td style="border: none; text-align: right; width: 60%; vertical-align: top;">
                <h3 style="margin: 0 0 5px 0; color: #333; border-bottom: 2px solid #ddd; display: inline-block;">
                    بيانات {{ $type == 'customer' ? 'العميل' : 'المورد' }}:
                </h3>
                <div style="margin-top: 5px; font-size: 16px;"><strong>الاسم:</strong> {{ $partner->name }}</div>
                <div><strong>الهاتف:</strong> {{ $partner->phone ?? '-' }}</div>
            </td>
            <td style="border: none; text-align: left; width: 40%; vertical-align: top;">
                <div style="background-color: #f9f9f9; padding: 10px; border: 1px solid #eee; border-radius: 5px;">
                    <div><strong>تاريخ الطباعة:</strong> {{ date('Y-m-d') }}</div>
                    <div><strong>نوع الكشف:</strong> {{ $type == 'customer' ? 'عملاء' : 'موردين' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <thead>
            <tr style="background-color: #333; color: white;">
                <th width="15%" style="padding: 10px; border: 1px solid #333;">التاريخ</th>
                <th width="40%" style="padding: 10px; border: 1px solid #333;">البيان</th>
                <th width="15%" style="padding: 10px; border: 1px solid #333;">مدين (عليه)</th>
                <th width="15%" style="padding: 10px; border: 1px solid #333;">دائن (له)</th>
                <th width="15%" style="padding: 10px; border: 1px solid #333;">الرصيد</th>
            </tr>
        </thead>
        <tbody>
            @php $balance = 0; @endphp
            @foreach($statement as $row)
                @php $balance += ($row['debit'] - $row['credit']); @endphp
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">{{ $row['date'] }}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">{{ $row['notes'] }} #{{ $row['ref'] }}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold; background-color: #fcfcfc;">{{ number_format($balance, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: left;">
        <table style="width: 40%; margin-right: auto; border-collapse: collapse;">
            <tr style="background-color: #333; color: white;">
                <td style="padding: 10px; border: 1px solid #333; text-align: right;">الرصيد النهائي</td>
                <td style="padding: 10px; border: 1px solid #333; text-align: center; font-size: 18px; font-weight: bold; direction: ltr;">
                    {{ number_format($balance, 2) }}
                </td>
            </tr>
        </table>
    </div>

@endsection
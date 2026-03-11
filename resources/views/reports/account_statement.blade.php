@extends('layouts.app')
@section('title', 'كشف حساب تفصيلي')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-file-invoice me-2"></i> كشف حساب تفصيلي</h3>
    
    <div class="d-print-none">
        @if($statementData)
        <button onclick="window.print()" class="btn btn-dark fw-bold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> طباعة التقرير
        </button>
        
        <div class="dropdown d-inline-block ms-2">
            <button class="btn btn-success dropdown-toggle shadow-sm fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-file-export me-1"></i> تصدير القائمة
            </button>
            <ul class="dropdown-menu shadow-sm border-0">
                <li>
                    <a class="dropdown-item text-danger fw-bold" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank">
                        <i class="fa-solid fa-file-pdf me-2"></i> تصدير PDF
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-success fw-bold" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                        <i class="fa-solid fa-file-excel me-2"></i> تصدير Excel
                    </a>
                </li>
            </ul>
        </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 bg-light d-print-none">
    <div class="card-body p-4">
        <form action="{{ route('reports.account_statement') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-bold">نوع الحساب</label>
                <select name="party_type" id="party_type" class="form-select" required onchange="togglePartyLists()">
                    <option value="">اختر...</option>
                    <option value="customer" {{ request('party_type') == 'customer' ? 'selected' : '' }}>عميل</option>
                    <option value="supplier" {{ request('party_type') == 'supplier' ? 'selected' : '' }}>مورد</option>
                </select>
            </div>
            <div class="col-md-3" id="customer_div" style="display: {{ request('party_type') == 'customer' ? 'block' : 'none' }};">
                <label class="form-label fw-bold text-success">اسم العميل</label>
                <select name="party_id" id="customer_select" class="form-select select2" {{ request('party_type') == 'customer' ? 'required' : '' }}>
                    <option value="">اختر العميل...</option>
                    @foreach($customers as $c) <option value="{{ $c->id }}" {{ request('party_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option> @endforeach
                </select>
            </div>
            <div class="col-md-3" id="supplier_div" style="display: {{ request('party_type') == 'supplier' ? 'block' : 'none' }};">
                <label class="form-label fw-bold text-danger">اسم المورد</label>
                <select name="party_id" id="supplier_select" class="form-select select2" {{ request('party_type') == 'supplier' ? 'required' : '' }}>
                    <option value="">اختر المورد...</option>
                    @foreach($suppliers as $s) <option value="{{ $s->id }}" {{ request('party_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option> @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">من تاريخ</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date', date('Y-m-01')) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-magnifying-glass me-1"></i> استخراج</button>
            </div>
        </form>
    </div>
</div>

@if($statementData)
<div class="card shadow-sm border-0 report-container">
    <div class="card-header bg-white p-4 border-bottom text-center">
        <h4 class="fw-bold mb-1">كشف حساب: {{ $statementData['party']->name }}</h4>
        <p class="text-muted mb-0">الفترة من: <span class="fw-bold">{{ request('from_date') }}</span> إلى: <span class="fw-bold">{{ request('to_date') }}</span></p>
    </div>
    <div class="card-body p-0">
        <div class="row g-0 border-bottom text-center">
            <div class="col-md-4 p-3 border-end">
                <span class="d-block text-muted mb-1">إجمالي المدين (عليه)</span>
                <h5 class="fw-bold text-dark">{{ number_format($statementData['total_debit'], 2) }}</h5>
            </div>
            <div class="col-md-4 p-3 border-end">
                <span class="d-block text-muted mb-1">إجمالي الدائن (له)</span>
                <h5 class="fw-bold text-dark">{{ number_format($statementData['total_credit'], 2) }}</h5>
            </div>
            <div class="col-md-4 p-3 bg-light">
                <span class="d-block text-muted mb-1">صافي الحركة (الرصيد النهائي)</span>
                <h4 class="fw-bold {{ $statementData['closing_balance'] >= 0 ? 'text-success' : 'text-danger' }}" dir="ltr">
                    {{ number_format($statementData['closing_balance'], 2) }}
                </h4>
            </div>
        </div>

        <div class="table-responsive p-3">
            <table class="table table-bordered table-striped align-middle text-center mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>التاريخ</th>
                        <th>المستند</th>
                        <th>رقم المرجع</th>
                        <th>البيان</th>
                        <th class="text-danger">مدين (Debit)</th>
                        <th class="text-success">دائن (Credit)</th>
                        <th class="bg-secondary text-white">الرصيد التراكمي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statementData['transactions'] as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td><span class="badge bg-secondary">{{ $row['document'] }}</span></td>
                        <td class="fw-bold text-primary">{{ $row['ref_no'] }}</td>
                        <td>{{ $row['description'] ?? '-' }}</td>
                        <td class="fw-bold text-danger">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                        <td class="fw-bold text-success">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
                        <td class="fw-bold bg-light" dir="ltr">{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="py-4 text-muted">لا توجد حركات خلال هذه الفترة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<style>
    @media print {
        body * { visibility: hidden; }
        .report-container, .report-container * { visibility: visible; }
        .report-container { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
        .d-print-none { display: none !important; }
        .table-dark th { background-color: #343a40 !important; color: white !important; }
    }
</style>
@endsection

@push('js')
<script>
    $(document).ready(function() { $('.select2').select2({ theme: 'bootstrap-5', dir: 'rtl' }); });

    function togglePartyLists() {
        let type = document.getElementById('party_type').value;
        let cDiv = document.getElementById('customer_div');
        let sDiv = document.getElementById('supplier_div');
        let cSelect = document.getElementById('customer_select');
        let sSelect = document.getElementById('supplier_select');

        cSelect.required = false; cSelect.name = '';
        sSelect.required = false; sSelect.name = '';

        if (type === 'customer') {
            cDiv.style.display = 'block'; sDiv.style.display = 'none';
            cSelect.required = true; cSelect.name = 'party_id';
        } else if (type === 'supplier') {
            cDiv.style.display = 'none'; sDiv.style.display = 'block';
            sSelect.required = true; sSelect.name = 'party_id';
        } else {
            cDiv.style.display = 'none'; sDiv.style.display = 'none';
        }
    }
    window.onload = togglePartyLists;
</script>
@endpush
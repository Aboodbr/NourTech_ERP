@extends('layouts.app')
@section('title', 'لوحة التحكم الرئيسية')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-chart-line me-2"></i> ملخص أداء النظام</h3>
    <span class="text-muted"><i class="fa-regular fa-calendar me-1"></i> {{ date('Y-m-d') }}</span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-primary border-4 h-100 bg-light">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-bold mb-2">رصيد الخزن الإجمالي</h6>
                    <h3 class="fw-bold mb-0 text-primary">{{ number_format($totalTreasury, 2) }}</h3>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                    <i class="fa-solid fa-vault text-primary fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-success border-4 h-100 bg-light">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-bold mb-2">إجمالي المبيعات</h6>
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($totalSales, 2) }}</h3>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                    <i class="fa-solid fa-file-invoice-dollar text-success fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-danger border-4 h-100 bg-light">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-bold mb-2">إجمالي المشتريات</h6>
                    <h3 class="fw-bold mb-0 text-danger">{{ number_format($totalPurchases, 2) }}</h3>
                </div>
                <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                    <i class="fa-solid fa-cart-shopping text-danger fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-warning border-4 h-100 bg-light">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-bold mb-2">تنبيهات النواقص</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ $shortagesCount }} <span class="fs-6 text-muted">صنف</span></h3>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                    <i class="fa-solid fa-triangle-exclamation text-warning fs-3"></i>
                </div>
            </div>
            <a href="{{ route('reports.shortages') }}" class="card-footer bg-transparent border-0 text-center text-decoration-none fw-bold text-muted small">
                عرض التفاصيل <i class="fa-solid fa-arrow-left ms-1"></i>
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white p-3 fw-bold"><i class="fa-solid fa-chart-pie me-2 text-primary"></i> المبيعات مقابل المشتريات</div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <canvas id="financeChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white p-3 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2 text-success"></i> أحدث المبيعات</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentSales as $sale)
                    <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="mb-0 fw-bold text-primary">{{ $sale->invoice_number }}</h6>
                            <small class="text-muted">{{ $sale->customer->name ?? 'عميل غير محدد' }}</small>
                        </div>
                        <span class="badge bg-success rounded-pill fs-6">{{ number_format($sale->total_amount, 2) }}</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted p-4">لا توجد مبيعات مسجلة بعد</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('financeChart');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['إجمالي المبيعات', 'إجمالي المشتريات'],
            datasets: [{
                data: [{{ $totalSales }}, {{ $totalPurchases }}],
                backgroundColor: ['#198754', '#dc3545'], // أخضر وأحمر
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'Tajawal', size: 14 } } }
            }
        }
    });
</script>
@endpush
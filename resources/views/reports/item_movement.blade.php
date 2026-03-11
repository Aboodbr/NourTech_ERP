@extends('layouts.app')
@section('title', 'حركة صنف')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-boxes-stacked me-2"></i> تقرير حركة صنف</h3>
    
    <div class="d-print-none">
        @if($product)
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
        <form action="{{ route('reports.item_movement') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold">اختر الصنف <span class="text-danger">*</span></label>
                <select name="product_id" class="form-select select2" required>
                    <option value="">بحث باسم الصنف أو الكود (SKU)...</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                            [{{ $p->sku }}] {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-bold">من تاريخ</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date', date('Y-m-01')) }}">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-bold">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date', date('Y-m-d')) }}">
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> استخراج الحركة
                </button>
            </div>
        </form>
    </div>
</div>

@if($product)
<div class="card shadow-sm border-0 report-container">
    <div class="card-header bg-white p-4 border-bottom text-center">
        <h4 class="fw-bold mb-1 text-primary">سجل حركة: {{ $product->name }}</h4>
        <p class="text-muted mb-0">كود الصنف: <span class="fw-bold">{{ $product->sku }}</span></p>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-bordered table-striped align-middle text-center mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>التاريخ والوقت</th>
                        <th>المخزن</th>
                        <th>نوع الحركة</th>
                        <th>الكمية (وارد / منصرف)</th>
                        <th>المستند المرجعي / البيان</th>
                        <th>المستخدم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mov)
                    <tr>
                        <td dir="ltr" class="fw-bold text-muted">{{ $mov->created_at->format('Y-m-d H:i') }}</td>
                        <td class="fw-bold">{{ $mov->stock->warehouse->name ?? 'غير محدد' }}</td>
                        <td>
                            @if($mov->quantity > 0)
                                <span class="badge bg-success"><i class="fa-solid fa-arrow-down me-1"></i> وارد</span>
                            @else
                                <span class="badge bg-danger"><i class="fa-solid fa-arrow-up me-1"></i> منصرف</span>
                            @endif
                        </td>
                        <td class="fw-bold fs-5 {{ $mov->quantity > 0 ? 'text-success' : 'text-danger' }}" dir="ltr">
                            {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity, 2) }}
                        </td>
                        <td>
                            @if($mov->reference)
                                <span class="badge bg-dark mb-1">
                                    {{ class_basename($mov->reference_type) }} #{{ $mov->reference->invoice_number ?? $mov->reference->id }}
                                </span><br>
                            @endif
                            <small class="text-muted">{{ $mov->notes ?? '-' }}</small>
                        </td>
                        <td>
                            <small class="text-muted"><i class="fa-solid fa-user-pen me-1"></i>{{ $mov->user->name ?? 'نظام' }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-5 text-muted fw-bold fs-5">لا توجد حركات مسجلة لهذا الصنف في الفترة المحددة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4 d-print-none">
    {{ $movements->links('pagination::bootstrap-5') }}
</div>
@endif

<style>
    @media print { 
        body * { visibility: hidden; } 
        .report-container, .report-container * { visibility: visible; } 
        .report-container { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; } 
        .d-print-none { display: none !important; } 
        .table-dark th { background-color: #343a40 !important; color: white !important; } 
    }
</style>
@endsection

@push('js')
<script>
    $(document).ready(function() { 
        $('.select2').select2({ theme: 'bootstrap-5', dir: 'rtl', width: '100%' }); 
    });
</script>
@endpush
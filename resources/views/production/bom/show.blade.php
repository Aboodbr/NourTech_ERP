@extends('layouts.app')

@section('title', 'تفاصيل مكونات المنتج (BOM)')

@section('content')
<div class="card shadow-sm mb-4 border-top border-amc-blue border-3">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-info-circle me-2 text-primary"></i> بيانات المعادلة الأساسية</h5>
        
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-sm btn-secondary me-2 shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
            <a href="{{ route('bom.edit', $bom->id) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit me-1"></i> تعديل المعادلة
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4 mb-3">
                <p class="text-muted mb-1 small">المنتج التام (النهائي)</p>
                <h5 class="fw-bold text-amc-red">{{ $bom->product->name ?? 'غير معروف' }}</h5>
            </div>
            <div class="col-md-4 mb-3">
                <p class="text-muted mb-1 small">اسم/وصف المعادلة</p>
                <h6 class="fw-bold">{{ $bom->name ?? '---' }}</h6>
            </div>
            <div class="col-md-4 mb-3">
                <p class="text-muted mb-1 small">حالة المعادلة</p>
                @if($bom->is_active)
                    <span class="badge bg-success px-3 py-2 fs-6"><i class="fas fa-check me-1"></i> مفعلة وجاهزة للإنتاج</span>
                @else
                    <span class="badge bg-danger px-3 py-2 fs-6"><i class="fas fa-times me-1"></i> معطلة</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light py-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-cubes me-2 text-amc-blue"></i> المواد الخام المطلوبة لإنتاج (قطعة واحدة)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0 text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="10%">م</th>
                        <th width="60%">المادة الخام (الصنف)</th>
                        <th width="30%">الكمية المطلوبة للقطعة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bom->items as $index => $item)
                        <tr>
                            <td class="fw-bold">{{ $index + 1 }}</td>
                            <td class="fw-bold text-start px-4">{{ $item->rawMaterial->name ?? 'صنف محذوف' }}</td>
                            <td>
                                <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
                                    {{ floatval($item->quantity) }} وحدة
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
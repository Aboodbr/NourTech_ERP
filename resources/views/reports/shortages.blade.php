@extends('layouts.app')
@section('title', 'تقرير النواقص')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> النواقص</h3>
    
    <div class="d-print-none">
        <button onclick="window.print()" class="btn btn-dark fw-bold shadow-sm">
            <i class="fa-solid fa-print me-1"></i> طباعة النواقص
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
    </div>
</div>

<div class="card mb-4 shadow-sm border-0 d-print-none border-top border-warning border-3 bg-body-tertiary">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('reports.shortages') }}" class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label text-secondary small fw-bold mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i> بحث في النواقص</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="اكتب للبحث (رقم، اسم الصنف، كود SKU)...">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">بحث</button>
                @if(request()->has('search') && request('search') != '')
                    <a href="{{ route('reports.shortages') }}" class="btn btn-outline-secondary w-100">
                        <i class="fa-solid fa-times me-1"></i> إلغاء
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 report-container">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-secondary">
                    <tr>
                        <th>كود الصنف</th>
                        <th>اسم الصنف</th>
                        <th>الحد الأدنى</th>
                        <th class="text-danger">الرصيد الفعلي</th>
                        <th>حالة النقص</th>
                        <th class="d-print-none">تم الطلب؟</th> {{-- مخفي عند الطباعة --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse($shortages as $item)
                    @php $currentStock = $item->stocks_sum_quantity ?? 0; @endphp
                    <tr id="row-{{ $item->id }}" class="{{ $item->is_ordered ? 'table-light opacity-75' : '' }}">
                        <td class="fw-bold">{{ $item->sku }}</td>
                        <td class="fw-bold text-primary">{{ $item->name }}</td>
                        <td class="fw-bold">{{ $item->min_stock }}</td>
                        <td class="fw-bold text-danger fs-5" dir="ltr">{{ number_format($currentStock, 1) }}</td>
                        <td>
                            @if($currentStock <= 0)
                                <span class="badge bg-danger">رصيد نافد</span>
                            @else
                                <span class="badge bg-warning text-body">تجاوز الحد</span>
                            @endif
                        </td>
                        <td class="d-print-none">
                            <div class="form-check d-flex justify-content-center">
                                <input class="form-check-input border-secondary toggle-ordered" type="checkbox" 
                                       data-id="{{ $item->id }}" 
                                       {{ $item->is_ordered ? 'checked' : '' }}
                                       style="width: 1.5em; height: 1.5em; cursor: pointer;">
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-5 text-success fw-bold">جميع الأصناف متوفرة ولا توجد نواقص!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-3 d-print-none">
    {{ $shortages->links('pagination::bootstrap-5') }}
</div>

<style>
    @media print { 
        body * { visibility: hidden; } 
        .report-container, .report-container * { visibility: visible; } 
        .report-container { position: absolute; left: 0; top: 0; width: 100%; border: none; } 
        .d-print-none { display: none !important; } 
        .table-secondary th { background-color: #343a40 !important; color: white !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.toggle-ordered');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const productId = this.dataset.id;
            const isChecked = this.checked ? 1 : 0;
            const row = document.getElementById(`row-${productId}`);

            // إضافة تأثير بصري أثناء الحفظ
            row.style.opacity = '0.5';

            fetch("{{ route('reports.shortages.toggle') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    id: productId,
                    is_ordered: isChecked
                })
            })
            .then(response => response.json())
            .then(data => {
                row.style.opacity = '1';
                if(data.success) {
                    if(isChecked) {
                        row.classList.add('table-light', 'opacity-75');
                    } else {
                        row.classList.remove('table-light', 'opacity-75');
                    }
                } else {
                    alert('حدث خطأ أثناء التحديث');
                    this.checked = !this.checked; // إرجاع الحالة الأصلية عند الفشل
                }
            })
            .catch(error => {
                row.style.opacity = '1';
                alert('خطأ في الاتصال بالسيرفر');
                this.checked = !this.checked;
            });
        });
    });
});
</script>
@endsection
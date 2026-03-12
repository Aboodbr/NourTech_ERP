{{-- Inventory Module: Main listing --}}
@extends('layouts.app')
@section('title', 'إدارة المخازن')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-boxes-stacked me-2"></i> جرد المخازن (NourTech)</h3>
    <div class="d-flex gap-2">
        @include('includes.export_buttons')
        <a href="{{ route('inventory.create') }}" class="btn btn-primary shadow-sm fw-bold">
            <i class="fa-solid fa-plus me-1"></i> تعريف صنف جديد
        </a>
        <button class="btn btn-warning shadow-sm fw-bold text-body" data-bs-toggle="modal" data-bs-target="#manualMoveModal">
            <i class="fa-solid fa-right-left me-1"></i> حركة يدوية
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 border-end border-primary border-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-bold mb-1">إجمالي المنتجات التامة</h6>
                    <h3 class="fw-bold mb-0 text-primary">{{ $totalFinished }}</h3>
                </div>
                <i class="fa-solid fa-box text-primary opacity-50" style="font-size: 3rem;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 border-end border-secondary border-4 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-bold mb-1">إجمالي المواد الخام</h6>
                    <h3 class="fw-bold mb-0 text-secondary">{{ $totalRaw }}</h3>
                </div>
                <i class="fa-solid fa-cubes text-secondary opacity-50" style="font-size: 3rem;"></i>
            </div>
        </div>
    </div>
</div>

@include('includes.search_panel')

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-0">
        @include('inventory.components._table')
    </div>
</div>

<div class="modal fade" id="manualMoveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-body-secondary">
                <h5 class="modal-title fw-bold">تسجيل حركة مخزنية يدوية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="moveStockForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">الصنف</label>
                        <select name="product_id" class="form-select select2" required style="width: 100%;">
                            <option value="">اختر الصنف...</option>
                            @foreach($productsList as $p)
                                <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">المخزن</label>
                        <select name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">نوع الحركة</label>
                            <select name="type" class="form-select" required>
                                <option value="manual_add">إضافة (تسوية بالزيادة)</option>
                                <option value="manual_deduct">خصم (تسوية بالعجز/توالف)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الكمية</label>
                            <input type="number" step="0.1" name="quantity" class="form-control" required min="0.1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات (سبب الحركة)</label>
                        <input type="text" name="notes" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning fw-bold text-body">تنفيذ الحركة</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
    $(document).ready(function() {
        // تفعيل بحث Select2 داخل المودال
        $('.select2').select2({ dropdownParent: $('#manualMoveModal'), theme: 'bootstrap-5', dir: 'rtl' });
    });

    // سكريبت إرسال الحركة اليدوية بالـ AJAX
    document.getElementById('moveStockForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        
        Swal.fire({title: 'جاري التنفيذ...', didOpen: () => Swal.showLoading()});

        fetch("{{ route('inventory.move') }}", {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire('تم!', result.body.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('خطأ!', result.body.message, 'error');
            }
        });
    });

    // سكريبت الحذف المباشر
    function deleteItem(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من استرجاع هذا الصنف!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/inventory/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) {
                        Swal.fire('تم الحذف!', result.body.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('مرفوض!', result.body.message, 'error');
                    }
                });
            }
        })
    }
</script>
@endpush
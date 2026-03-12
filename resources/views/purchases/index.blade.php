{{-- Purchases Module: Main layout for indexing and filtering --}}
@extends('layouts.app')
@section('title', 'إدارة المشتريات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-cart-shopping me-2"></i> فواتير المشتريات</h3>
    <div class="d-flex gap-2">
        @include('includes.export_buttons')
        
        <a href="{{ route('suppliers.index') }}" class="btn btn-info text-white fw-bold shadow-sm">
            <i class="fa-solid fa-users me-1"></i> قائمة الموردين
        </a>
        <a href="{{ route('suppliers.create') }}" class="btn btn-warning fw-bold text-body shadow-sm">
            <i class="fa-solid fa-user-plus me-1"></i> مورد جديد
        </a>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> فاتورة مشتريات
        </a>
    </div>
</div>

@include('includes.search_panel')

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-0">
        @include('purchases.components._table')
    </div>
</div>
<div class="modal fade" id="showInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-body-secondary">
                <h5 class="modal-title fw-bold">تفاصيل فاتورة المشتريات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6"><p><strong>رقم الفاتورة:</strong> <span id="modal_inv_number"></span></p></div>
                    <div class="col-md-6"><p><strong>تاريخ الفاتورة:</strong> <span id="modal_inv_date"></span></p></div>
                    <div class="col-md-6"><p><strong>المورد:</strong> <span id="modal_supplier"></span></p></div>
                    <div class="col-md-6"><p><strong>المخزن المستلم:</strong> <span id="modal_warehouse"></span></p></div>
                    <div class="col-md-6"><p><strong>الحالة:</strong> <span id="modal_status"></span></p></div>
                    <div class="col-md-6"><p><strong>الإجمالي:</strong> <span id="modal_total" class="text-danger fw-bold"></span></p></div>
                </div>
                <div class="table-responsive"><table class="table table-bordered table-sm text-center">
                    <thead class="table-secondary">
                        <tr>
                            <th>الصنف (المادة الخام)</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody id="modal_items_body"></tbody>
                </table></div>
                <p><strong>ملاحظات:</strong> <span id="modal_notes"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    // 1. زر الترحيل (Approve)
    function approveInvoice(id) {
        Swal.fire({
            title: 'تأكيد الترحيل', text: "سيتم إضافة الكميات للمخزن ولا يمكن التراجع!",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ffc107', cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، رحّل الفاتورة', cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/purchases/${id}/approve`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) {
                        Swal.fire('تم!', result.body.message, 'success').then(() => location.reload());
                    } else { Swal.fire('خطأ!', result.body.message, 'error'); }
                });
            }
        });
    }

    // 2. زر الحذف (Delete)
    function deleteInvoice(id) {
        Swal.fire({
            title: 'تأكيد الحذف', text: "هل أنت متأكد من حذف هذه الفاتورة (المسودة)؟",
            icon: 'error', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، احذف', cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/purchases/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) {
                        Swal.fire('تم الحذف!', result.body.message, 'success').then(() => location.reload());
                    } else { Swal.fire('مرفوض!', result.body.message, 'error'); }
                });
            }
        });
    }

    // 3. زر العرض (Show Details)
    function showInvoice(id) {
        Swal.fire({title: 'جاري التحميل...', didOpen: () => Swal.showLoading()});
        fetch(`/purchases/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            document.getElementById('modal_inv_number').innerText = data.invoice_number;
            document.getElementById('modal_inv_date').innerText = data.invoice_date;
            document.getElementById('modal_supplier').innerText = data.supplier ? data.supplier.name : '-';
            document.getElementById('modal_warehouse').innerText = data.warehouse ? data.warehouse.name : '-';
            document.getElementById('modal_status').innerHTML = data.status === 'approved' ? '<span class="badge bg-success">مرحلة</span>' : '<span class="badge bg-warning text-body">مسودة</span>';
            document.getElementById('modal_total').innerText = parseFloat(data.total_amount).toFixed(2);
            document.getElementById('modal_notes').innerText = data.notes || 'لا يوجد';

            let tbody = '';
            data.items.forEach(item => {
                let total = (item.quantity * item.unit_price).toFixed(2);
                let productName = item.product ? item.product.name : 'صنف محذوف';
                tbody += `<tr><td>${productName}</td><td>${item.quantity}</td><td>${item.unit_price}</td><td>${total}</td></tr>`;
            });
            document.getElementById('modal_items_body').innerHTML = tbody;

            new bootstrap.Modal(document.getElementById('showInvoiceModal')).show();
        }).catch(err => Swal.fire('خطأ', 'فشل تحميل البيانات', 'error'));
    }
</script>
@endpush
@endsection
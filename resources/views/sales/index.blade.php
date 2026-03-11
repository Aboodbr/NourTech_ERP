@extends('layouts.app')
@section('title', 'إدارة المبيعات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-file-invoice-dollar me-2"></i> فواتير المبيعات</h3>
    <div class="d-flex gap-2">
        @include('includes.export_buttons')
        
        <a href="{{ route('customers.index') }}" class="btn btn-info text-white fw-bold shadow-sm">
            <i class="fa-solid fa-users me-1"></i> قائمة العملاء
        </a>
        <a href="{{ route('customers.create') }}" class="btn btn-warning fw-bold text-dark shadow-sm">
            <i class="fa-solid fa-user-plus me-1"></i> عميل جديد
        </a>
        <a href="{{ route('sales.create') }}" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> فاتورة مبيعات
        </a>
    </div>
</div>

@include('includes.search_panel')

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-0">
        @include('sales.components._table')
    </div>
</div>

<div class="modal fade" id="showInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">تفاصيل فاتورة المبيعات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6"><p><strong>رقم الفاتورة:</strong> <span id="modal_inv_number"></span></p></div>
                    <div class="col-md-6"><p><strong>تاريخ الفاتورة:</strong> <span id="modal_inv_date"></span></p></div>
                    <div class="col-md-6"><p><strong>العميل:</strong> <span id="modal_customer"></span></p></div>
                    <div class="col-md-6"><p><strong>المخزن الصادر منه:</strong> <span id="modal_warehouse"></span></p></div>
                    <div class="col-md-6"><p><strong>الحالة:</strong> <span id="modal_status"></span></p></div>
                    <div class="col-md-6"><p><strong>الإجمالي:</strong> <span id="modal_total" class="text-danger fw-bold"></span></p></div>
                </div>
                <table class="table table-bordered table-sm text-center">
                    <thead class="table-light">
                        <tr>
                            <th>الصنف (منتج تام)</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody id="modal_items_body"></tbody>
                </table>
                <p><strong>ملاحظات:</strong> <span id="modal_notes"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    function approveInvoice(id) {
        Swal.fire({
            title: 'تأكيد الترحيل', text: "سيتم خصم الكميات من المخزن ولا يمكن التراجع!",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ffc107', cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، رحّل الفاتورة'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/sales/${id}/approve`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) { Swal.fire('تم!', result.body.message, 'success').then(() => location.reload()); } 
                    else { Swal.fire('خطأ!', result.body.message, 'error'); }
                });
            }
        });
    }

    function deleteInvoice(id) {
        Swal.fire({
            title: 'تأكيد الحذف', text: "هل أنت متأكد من الحذف؟",
            icon: 'error', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، احذف'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/sales/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) { Swal.fire('تم الحذف!', result.body.message, 'success').then(() => location.reload()); } 
                    else { Swal.fire('مرفوض!', result.body.message, 'error'); }
                });
            }
        });
    }

    function showInvoice(id) {
        Swal.fire({title: 'جاري التحميل...', didOpen: () => Swal.showLoading()});
        fetch(`/sales/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            document.getElementById('modal_inv_number').innerText = data.invoice_number;
            document.getElementById('modal_inv_date').innerText = data.invoice_date;
            document.getElementById('modal_customer').innerText = data.customer ? data.customer.name : '-';
            document.getElementById('modal_warehouse').innerText = data.warehouse ? data.warehouse.name : '-';
            document.getElementById('modal_status').innerHTML = data.status === 'approved' ? '<span class="badge bg-success">مرحلة</span>' : '<span class="badge bg-warning text-dark">مسودة</span>';
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
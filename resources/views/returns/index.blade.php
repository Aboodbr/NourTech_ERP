@extends('layouts.app')
@section('title', 'المرتجعات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-rotate-left me-2"></i> إدارة المرتجعات</h3>
    <a href="{{ route('returns.create') }}" class="btn btn-primary fw-bold shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> تسجيل مرتجع جديد
    </a>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-secondary">
                    <tr>
                        <th>رقم المرتجع</th>
                        <th>النوع</th>
                        <th>الجهة</th>
                        <th>المخزن</th>
                        <th>الإجمالي</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $rt)
                    <tr>
                        <td class="fw-bold text-muted">RT-{{ str_pad($rt->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            @if($rt->type == 'sales_return')
                                <span class="badge bg-primary">مرتجع مبيعات</span>
                            @else
                                <span class="badge bg-secondary">مرتجع مشتريات</span>
                            @endif
                        </td>
                        <td class="fw-bold">{{ $rt->model->name ?? '-' }}</td>
                        <td>{{ $rt->warehouse->name ?? '-' }}</td>
                        <td class="fw-bold text-danger" dir="ltr">{{ number_format($rt->total_amount, 2) }}</td>
                        <td>{{ $rt->return_date }}</td>
                        <td>
                            @if($rt->status == 'approved')
                                <span class="badge bg-success">مرحلة</span>
                            @else
                                <span class="badge bg-warning text-body">قيد الانتظار</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="showReturn({{ $rt->id }})" title="عرض"><i class="fa-solid fa-eye"></i></button>
                                @if($rt->status == 'pending')
                                    <button class="btn btn-outline-danger" onclick="deleteReturn({{ $rt->id }})" title="حذف"><i class="fa-solid fa-trash"></i></button>
                                    <button class="btn btn-outline-warning text-body fw-bold" onclick="approveReturn({{ $rt->id }})" title="ترحيل المرتجع"><i class="fa-solid fa-check-double"></i> ترحيل</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted fw-bold">لا توجد مرتجعات مسجلة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $returns->links('pagination::bootstrap-5') }}</div>
    </div>
</div>

<div class="modal fade" id="showReturnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">تفاصيل المرتجع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3 bg-light p-3 rounded">
                    <div class="col-md-4 mb-2"><p class="mb-0"><strong>النوع:</strong> <span id="modal_type"></span></p></div>
                    <div class="col-md-4 mb-2"><p class="mb-0"><strong>التاريخ:</strong> <span id="modal_date"></span></p></div>
                    <div class="col-md-4 mb-2"><p class="mb-0"><strong>الجهة:</strong> <span id="modal_model" class="text-primary fw-bold"></span></p></div>
                    <div class="col-md-4 mb-2"><p class="mb-0"><strong>المخزن:</strong> <span id="modal_warehouse"></span></p></div>
                    <div class="col-md-4 mb-2"><p class="mb-0"><strong>الحالة:</strong> <span id="modal_status"></span></p></div>
                    <div class="col-md-4 mb-2"><p class="mb-0"><strong>الإجمالي:</strong> <span id="modal_total" class="text-danger fw-bold fs-5" dir="ltr"></span></p></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>الصنف</th>
                                <th>الكمية المستردة</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody id="modal_items_body"></tbody>
                    </table>
                </div>
                <p class="mt-2 text-muted"><strong>ملاحظات:</strong> <span id="modal_notes"></span></p>
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
    function approveReturn(id) {
        Swal.fire({
            title: 'تأكيد الترحيل', 
            text: "سيتم تطبيق التعديلات المخزنية والمالية. لا يمكن التراجع عن هذه الخطوة!",
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'نعم، رحّل المرتجع الآن'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({title: 'جاري الترحيل...', didOpen: () => Swal.showLoading()});
                fetch(`/returns/${id}/approve`, { 
                    method: 'POST', 
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } 
                })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) { 
                        Swal.fire('تم الترحيل!', result.body.message, 'success').then(() => location.reload()); 
                    } else { 
                        Swal.fire('خطأ!', result.body.message, 'error'); 
                    }
                });
            }
        });
    }

    function deleteReturn(id) {
        Swal.fire({
            title: 'تأكيد الحذف', 
            text: "هل أنت متأكد من حذف مسودة هذا المرتجع؟",
            icon: 'error', 
            showCancelButton: true, 
            confirmButtonColor: '#d33',
            confirmButtonText: 'نعم، احذف'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/returns/${id}`, { 
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
        });
    }

    function showReturn(id) {
        Swal.fire({title: 'جاري التحميل...', didOpen: () => Swal.showLoading()});
        fetch(`/returns/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            Swal.close();
            document.getElementById('modal_type').innerText = data.type === 'sales_return' ? 'مبيعات' : 'مشتريات';
            document.getElementById('modal_date').innerText = data.return_date;
            document.getElementById('modal_model').innerText = data.model ? data.model.name : '-';
            document.getElementById('modal_warehouse').innerText = data.warehouse ? data.warehouse.name : '-';
            document.getElementById('modal_status').innerHTML = data.status === 'approved' ? '<span class="badge bg-success">مرحلة</span>' : '<span class="badge bg-warning text-body">قيد الانتظار</span>';
            document.getElementById('modal_total').innerText = parseFloat(data.total_amount).toFixed(2);
            document.getElementById('modal_notes').innerText = data.notes || 'لا يوجد ملاحظات';

            let tbody = '';
            data.items.forEach(item => {
                let total = (item.quantity * item.unit_price).toFixed(2);
                let productName = item.product ? item.product.name : 'منتج محذوف';
                tbody += `<tr><td>${productName}</td><td class="fw-bold">${item.quantity}</td><td>${item.unit_price}</td><td class="fw-bold text-danger">${total}</td></tr>`;
            });
            document.getElementById('modal_items_body').innerHTML = tbody;
            new bootstrap.Modal(document.getElementById('showReturnModal')).show();
        }).catch(err => Swal.fire('خطأ', 'فشل تحميل بيانات المرتجع', 'error'));
    }
</script>
@endpush
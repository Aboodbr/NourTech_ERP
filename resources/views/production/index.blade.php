@extends('layouts.app')

@section('title', 'إدارة التصنيع')

@push('css')
<style>
    .card-box { border-radius: 10px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
    .card-box:hover { transform: translateY(-5px); }
    .icon-circle { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; }
</style>
@endpush

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-secondary fw-bold"> خطة الإنتاج</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createOrderModal">
            <i class="fa-solid fa-plus-circle me-1"></i> أمر شغل جديد
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card card-box bg-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">قيد الانتظار (مخطط)</h6>
                        <h2 class="fw-bold text-warning">{{ $plannedCount }}</h2>
                    </div>
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-box bg-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">تم الإنتاج (مكتمل)</h6>
                        <h2 class="fw-bold text-success">{{ $completedCount }}</h2>
                    </div>
                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-check-double"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-box bg-body p-0">
        <div class="card-header bg-body py-3">
            <h5 class="mb-0 text-secondary"><i class="fa-solid fa-list me-2"></i> سجل أوامر التصنيع</h5>
        </div>
        <div class="table-responsive">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th class="ps-4">رقم الأمر</th>
                        <th>المنتج المطلوب</th>
                        <th>المخزن</th>
                        <th>الكمية</th>
                        <th>تاريخ الإنتاج</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $order->order_number }}</td>
                        <td>
                            <span class="fw-bold text-body">{{ $order->product->name }}</span>
                        </td>
                        <td>{{ $order->warehouse->name }}</td>
                        <td class="fw-bold fs-5">{{ $order->quantity }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->production_date)->format('Y-m-d') }}</td>
                        <td>
                            @if($order->status == 'planned')
                                <span class="status-badge bg-warning text-body"><i class="fa-regular fa-clock"></i> مخطط</span>
                            @else
                                <span class="status-badge bg-success text-white"><i class="fa-solid fa-check"></i> مكتمل</span>
                            @endif
                        </td>
                        <td>
                            @if($order->status == 'planned')
                                <button onclick="executeOrder({{ $order->id }})" class="btn btn-sm btn-success shadow-sm">
                                    <i class="fa-solid fa-gears me-1"></i> تنفيذ التصنيع
                                </button>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled>تم التنفيذ</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">لا توجد أوامر شغل حالياً</td>
                    </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    @include('production.partials.create-modal')

@endsection

@push('js')
<script>
    // 1. كود إنشاء أمر جديد
    document.getElementById('createOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const modal = bootstrap.Modal.getInstance(document.getElementById('createOrderModal'));
        modal.hide();

        fetch("{{ route('production.store') }}", {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم التخطيط',
                    text: result.body.message,
                    timer: 1500, showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire({icon: 'error', title: 'خطأ', text: result.body.message});
            }
        });
    });

    // 2. كود تنفيذ التصنيع (الزر الأخضر)
    function executeOrder(id) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "سيتم خصم الخامات وإضافة المنتج التام للمخزن فوراً.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، ابدأ التصنيع!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // إظهار Loading
                Swal.fire({title: 'جاري المعالجة...', didOpen: () => Swal.showLoading()});

                fetch(`/production/${id}/complete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) {
                        Swal.fire('تم!', result.body.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('خطأ!', result.body.message, 'error'); // سيظهر هنا لو الخامات غير كافية
                    }
                });
            }
        })
    }
    
    // تفعيل Select2 داخل المودال (إذا كنت تستخدمه)
    $(document).ready(function() {
        $('#prod_product_select').select2({
            theme: 'bootstrap-5', dir: "rtl", dropdownParent: $('#createOrderModal'), width: '100%'
        });
    });
</script>
@endpush
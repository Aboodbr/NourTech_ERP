@extends('layouts.app')
@section('title', 'إدارة العملاء')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-users me-2"></i> قائمة العملاء</h3>
    <a href="{{ route('customers.create') }}" class="btn btn-primary fw-bold shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> إضافة عميل جديد
    </a>
</div>

<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form action="{{ route('customers.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="ابحث باسم العميل أو رقم الهاتف..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i> بحث</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>اسم العميل</th>
                        <th>رقم الهاتف</th>
                        <th>العنوان</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td class="fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-bold text-primary">{{ $customer->name }}</td>
                        <td dir="ltr">{{ $customer->phone ?? '-' }}</td>
                        <td>{{ $customer->address ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-success" title="تعديل بيانات العميل"><i class="fa-solid fa-pen"></i></a>
                            
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCustomer({{ $customer->id }})" title="حذف العميل">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">لا يوجد عملاء مسجلين</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="d-flex justify-content-center mt-3">{{ $customers->links('pagination::bootstrap-5') }}</div>
@endsection
@push('js')
<script>
    function deleteCustomer(id) {
        Swal.fire({
            title: 'تأكيد الحذف', 
            text: "هل أنت متأكد من حذف هذا العميل؟ لا يمكن التراجع عن هذه الخطوة.",
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#d33', 
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، احذف', 
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // إرسال طلب الحذف في الخلفية
                fetch(`/customers/${id}`, { 
                    method: 'DELETE', 
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                        'Accept': 'application/json' 
                    } 
                })
                .then(res => res.json().then(data => ({status: res.status, body: data})))
                .then(result => {
                    if(result.status === 200) { 
                        Swal.fire('تم الحذف!', result.body.message, 'success').then(() => location.reload()); 
                    } else { 
                        Swal.fire('مرفوض!', result.body.message, 'error'); 
                    }
                }).catch(err => {
                    Swal.fire('خطأ!', 'حدث مشكلة في الاتصال بالخادم.', 'error');
                });
            }
        });
    }
</script>
@endpush
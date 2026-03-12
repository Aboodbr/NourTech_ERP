{{-- Sales Module: Create new invoice form --}}
@extends('layouts.app')
@section('title', 'إنشاء فاتورة مبيعات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">فاتورة مبيعات جديدة</h3>
    <a href="{{ url()->previous() }}" class="btn btn-secondary shadow-sm fw-bold">
        <i class="fa-solid fa-arrow-right-long me-1"></i> رجوع للخلف
    </a>
</div>

<form id="saleForm">
    @csrf
    @include('sales.components._form')
</form>
@endsection

@push('js')
<script>
    document.getElementById('saleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({title: 'جاري حفظ الفاتورة...', didOpen: () => Swal.showLoading()});
        
        fetch("{{ route('sales.store') }}", {
            method: "POST", 
            body: new FormData(this),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire('تم!', result.body.message, 'success').then(() => window.location.href = "{{ route('sales.index') }}");
            } else { 
                Swal.fire('خطأ!', result.body.message, 'error'); 
            }
        }).catch(err => {
            Swal.fire('خطأ!', 'فشل الاتصال بالخادم', 'error');
        });
    });
</script>
@endpush
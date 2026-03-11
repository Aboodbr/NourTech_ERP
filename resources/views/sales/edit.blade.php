@extends('layouts.app')
@section('title', 'تعديل فاتورة المبيعات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">تعديل الفاتورة رقم: <span class="text-primary">{{ $invoice->invoice_number }}</span></h3>
    <a href="{{ url()->previous() }}" class="btn btn-secondary shadow-sm fw-bold">
        <i class="fa-solid fa-arrow-right-long me-1"></i> رجوع للخلف
    </a>
</div>

<form id="editSaleForm">
    @csrf
    @method('PUT')
    @include('sales.components._form', ['invoice' => $invoice])
</form>
@endsection

@push('js')
<script>
    document.getElementById('editSaleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({title: 'جاري التحديث...', didOpen: () => Swal.showLoading()});
        
        let formData = new FormData(this);
        
        fetch("{{ route('sales.update', $invoice->id) }}", {
            method: "POST", // نستخدم POST لأن FormData ترسل _method=PUT
            body: formData,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire('تم!', result.body.message, 'success').then(() => window.location.href = "{{ route('sales.index') }}");
            } else { 
                Swal.fire('خطأ!', result.body.message, 'error'); 
            }
        });
    });
</script>
@endpush
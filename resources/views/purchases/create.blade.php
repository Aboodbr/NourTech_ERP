{{-- Purchases Module: Create new purchase invoice --}}
@extends('layouts.app')
@section('title', 'فاتورة مشتريات جديدة')
@section('content')
<h4 class="mb-4 fw-bold text-secondary">إنشاء فاتورة مشتريات واردة</h4>

<form id="purchaseForm">
    @csrf
    @include('purchases.components._form')
</form>
@endsection
@push('js')
<script>
    document.getElementById('purchaseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({title: 'جاري الحفظ...', didOpen: () => Swal.showLoading()});
        fetch("{{ route('purchases.store') }}", {
            method: "POST", body: new FormData(this),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire('تم!', result.body.message, 'success').then(() => window.location.href = "{{ route('purchases.index') }}");
            } else { Swal.fire('خطأ!', result.body.message, 'error'); }
        });
    });
</script>
@endpush
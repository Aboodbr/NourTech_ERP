@extends('layouts.app')
@section('title', 'إضافة عميل')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">إضافة عميل جديد</h3>
    <a href="{{ url()->previous() }}" class="btn btn-secondary shadow-sm fw-bold">
        <i class="fa-solid fa-arrow-right-long me-1"></i> رجوع للخلف
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf
            @include('customers._form', ['customer' => new \App\Models\Customer()])
        </form>
    </div>
</div>
@endsection
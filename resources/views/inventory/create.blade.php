@extends('layouts.app')
@section('title', 'إضافة مادة جديدة')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">إضافة مادة / صنف جديد للمخزن</h3>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-right me-1"></i> العودة للقائمة</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            @include('inventory.components._form', ['item' => new \App\Models\Product()])
        </form>
    </div>
</div>
@endsection
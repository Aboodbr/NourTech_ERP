@extends('layouts.app')
@section('title', 'تعديل مورد')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">تعديل بيانات المورد</h3>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('suppliers._form', ['supplier' => $supplier])
        </form>
    </div>
</div>
@endsection
@extends('layouts.app')
@section('title', 'تعديل بيانات المادة')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">تعديل: <span class="text-primary">{{ $inventory->name }}</span></h3>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('inventory.update', $inventory->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('inventory.components._form', ['item' => $inventory])
        </form>
    </div>
</div>
@endsection
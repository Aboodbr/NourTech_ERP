@extends('layouts.app')
@section('title', 'إضافة مورد جديد')
@section('content')

<div class="container py-4">
    <div class="card shadow-sm border-0" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fa-solid fa-truck-field me-2"></i> إضافة مورد جديد</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">اسم المورد / الشركة <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="text-end mt-4">
                    <a href="{{ route('purchases.create') }}" class="btn btn-secondary me-2">إلغاء</a>
                    <button type="submit" class="btn btn-success px-4">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
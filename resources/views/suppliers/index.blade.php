@extends('layouts.app')
@section('title', 'إدارة الموردين')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-users me-2"></i> قائمة الموردين</h3>
    
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary fw-bold shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> إضافة مورد جديد
    </a>
</div>

<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form action="{{ route('suppliers.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="ابحث باسم المورد أو رقم الهاتف..." value="{{ request('search') }}">
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
                        <th>اسم المورد</th>
                        <th>رقم الهاتف</th>
                        <th>العنوان</th>
                        <th class="text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td class="fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-bold">{{ $supplier->name }}</td>
                        <td dir="ltr">{{ $supplier->phone ?? '-' }}</td>
                        <td>{{ $supplier->address ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-pen"></i></a>
                            
                            </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">لا يوجد موردين مسجلين</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="d-flex justify-content-center mt-3">{{ $suppliers->links('pagination::bootstrap-5') }}</div>
@endsection
@extends('layouts.app')

@section('title', 'معادلات التصنيع (BOM)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-secondary mb-0"><i class="fas fa-sitemap me-2 text-primary"></i> إدارة تركيبة المنتجات</h4>
    <a href="{{ route('bom.create') }}" class="btn btn-primary fw-bold px-4 shadow-sm">
        <i class="fas fa-plus me-2"></i> إنشاء معادلة جديدة
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 bg-success text-white" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <div class="table-responsive"><table class="table table-hover table-striped mb-0 text-center align-middle">
                <thead class="table-secondary">
                    <tr>
                        <th width="5%">#</th>
                        <th width="25%">المنتج التام (النهائي)</th>
                        <th width="25%">اسم / وصف المعادلة</th>
                        <th width="15%">عدد الخامات</th>
                        <th width="15%">الحالة</th>
                        <th width="15%">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($boms as $bom)
                        <tr>
                            <td class="fw-bold text-secondary">{{ $loop->iteration }}</td>
                            <td class="fw-bold text-primary">{{ $bom->product->name ?? 'منتج غير معروف' }}</td>
                            <td>{{ $bom->name ?? 'معادلة قياسية' }}</td>
                            <td>
                                <span class="badge bg-secondary rounded-pill px-3 py-2">
                                    {{ $bom->items()->count() }} خامات
                                </span>
                            </td>
                            <td>
                                @if($bom->is_active)
                                    <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> مفعلة</span>
                                @else
                                    <span class="badge bg-danger px-3 py-2"><i class="fas fa-times-circle me-1"></i> معطلة</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('bom.show', $bom->id) }}" class="btn btn-sm btn-info text-white shadow-sm" title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <a href="{{ route('bom.edit', $bom->id) }}" class="btn btn-sm btn-warning shadow-sm" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form action="{{ route('bom.destroy', $bom->id) }}" method="POST" class="d-inline-block form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger shadow-sm btn-delete" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-folder-open fs-1 mb-3 text-secondary opacity-50"></i>
                                <h5>لا توجد معادلات تصنيع مسجلة حتى الآن</h5>
                                <p class="mb-0">قم بإضافة أول معادلة تصنيع لربط المواد الخام بالمنتجات التامة.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    
    @if($boms->hasPages())
        <div class="card-footer bg-body border-0 pt-3">
            {{ $boms->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

@push('js')
<script>
    // تفعيل نافذة التأكيد قبل الحذف باستخدام SweetAlert2 المدمج في الـ Layout الخاص بك
    $(document).ready(function() {
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            let form = $(this).closest('form');
            
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من استرجاع معادلة التصنيع هذه!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذفها!',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
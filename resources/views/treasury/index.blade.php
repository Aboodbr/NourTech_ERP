@extends('layouts.app')
@section('title', 'الخزينة والحسابات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-vault me-2"></i> إدارة الخزينة والسندات</h3>
    
    <div class="d-flex gap-2 d-print-none">
        <div class="dropdown d-inline-block">
            <button class="btn btn-success dropdown-toggle shadow-sm fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-file-export me-1"></i> تصدير السجل
            </button>
            <ul class="dropdown-menu shadow-sm border-0">
                <li>
                    <a class="dropdown-item text-danger fw-bold" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank">
                        <i class="fa-solid fa-file-pdf me-2"></i> تصدير PDF
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-success fw-bold" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                        <i class="fa-solid fa-file-excel me-2"></i> تصدير Excel
                    </a>
                </li>
            </ul>
        </div>

        <a href="{{ route('treasury.create') }}" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> إصدار سند (قبض / صرف)
        </a>
    </div>
</div>

<div class="row mb-4">
    @forelse($treasuries as $treasury)
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 border-start border-success border-4 h-100 bg-light">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted fw-bold mb-1">{{ $treasury->name }}</h6>
                    <h3 class="fw-bold mb-0 text-success" dir="ltr">{{ number_format($treasury->balance, 2) }}</h3>
                </div>
                <i class="fa-solid fa-money-bill-wave text-success opacity-50" style="font-size: 3rem;"></i>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-warning border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i> لا توجد خزن نشطة حالياً. يرجى إضافة خزنة من قاعدة البيانات للبدء.</div>
    </div>
    @endforelse
</div>

<div class="card mb-4 shadow-sm border-0 d-print-none" style="background-color: #f8f9fa; border-top: 3px solid var(--amc-blue) !important;">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('treasury.index') }}" class="row g-2 align-items-end">
            
            <div class="col-md-4">
                <label class="form-label text-secondary small fw-bold mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i> بحث عام</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="رقم السند، البيان، المبلغ، العميل...">
            </div>

            <div class="col-md-2">
                <label class="form-label text-secondary small fw-bold mb-1">نوع الحركة</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>مقبوضات (قبض)</option>
                    <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>مدفوعات (صرف)</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-secondary small fw-bold mb-1">من تاريخ</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label text-secondary small fw-bold mb-1">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">بحث</button>
                @if(request()->hasAny(['search', 'type', 'from_date', 'to_date']))
                    <a href="{{ route('treasury.index') }}" class="btn btn-outline-secondary btn-sm w-100">إلغاء</a>
                @endif
            </div>

        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mt-2">
    <div class="card-body p-0">
        
        @if(session('success'))
            <div class="alert alert-success m-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger m-3">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr>
                        <th>رقم السند</th>
                        <th>التاريخ</th>
                        <th>نوع الحركة</th>
                        <th>الجهة (العميل / المورد)</th>
                        <th>المبلغ</th>
                        <th>البيان</th>
                        <th>المستخدم</th>
                        <th class="d-print-none">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trans)
                    <tr>
                        <td class="fw-bold text-muted">#{{ $trans->id }}</td>
                        <td dir="ltr">{{ $trans->transaction_date }}</td>
                        <td>
                            @if($trans->type == 'income')
                                <span class="badge bg-success"><i class="fa-solid fa-arrow-down me-1"></i> سند قبض</span>
                            @else
                                <span class="badge bg-danger"><i class="fa-solid fa-arrow-up me-1"></i> سند صرف</span>
                            @endif
                        </td>
                        <td class="fw-bold text-primary">
                            {{ $trans->model->name ?? 'حركة عامة / منصرفات' }}
                        </td>
                        <td class="fw-bold {{ $trans->type == 'income' ? 'text-success' : 'text-danger' }} fs-6" dir="ltr">
                            {{ number_format($trans->amount, 2) }}
                        </td>
                        <td>{{ $trans->description }}</td>
                        <td>
                            <small class="text-muted"><i class="fa-solid fa-user-pen me-1"></i>{{ $trans->user->name ?? 'نظام' }}</small>
                        </td>
                        <td class="d-print-none">
                            <a href="{{ route('treasury.edit', $trans->id) }}" class="btn btn-sm btn-outline-warning shadow-sm" title="تعديل">
                                <i class="fa-solid fa-edit"></i>
                            </a>
                            <form action="{{ route('treasury.destroy', $trans->id) }}" method="POST" class="d-inline-block form-delete">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm btn-delete" title="حذف">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted fw-bold">لا توجد حركات مالية مسجلة مطابقة للبحث</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="d-flex justify-content-center mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        // رسالة تأكيد الحذف
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            let form = $(this).closest('form');
            
            Swal.fire({
                title: 'هل أنت متأكد من الحذف؟',
                text: "سيتم التراجع عن هذه الحركة محاسبياً وتحديث رصيد الخزينة!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، احذف السند!',
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
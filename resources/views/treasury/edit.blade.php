@extends('layouts.app')
@section('title', 'تعديل سند')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-pen-to-square me-2"></i> تعديل سند رقم #{{ $transaction->id }}</h3>
    <a href="{{ route('treasury.index') }}" class="btn btn-secondary fw-bold shadow-sm">
        <i class="fa-solid fa-arrow-right me-1"></i> رجوع للسجل
    </a>
</div>

<div class="card shadow-sm border-0 border-top border-warning border-3 bg-body-secondary">
    <div class="card-body p-4">
        <form id="editTreasuryForm">
            @csrf
            @method('PUT')
            
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">نوع الحركة <span class="text-danger">*</span></label>
                    <select name="type" id="type_select" class="form-select" required onchange="togglePartyLists()">
                        <option value="income" {{ $transaction->type == 'income' ? 'selected' : '' }}>سند قبض (مقبوضات)</option>
                        <option value="expense" {{ $transaction->type == 'expense' ? 'selected' : '' }}>سند صرف (مدفوعات)</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">الخزينة <span class="text-danger">*</span></label>
                    <select name="treasury_id" class="form-select" required>
                        @foreach($treasuries as $tr)
                            <option value="{{ $tr->id }}" {{ $transaction->treasury_id == $tr->id ? 'selected' : '' }}>{{ $tr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3" id="customer_div" style="display: {{ $transaction->type == 'income' ? 'block' : 'none' }};">
                    <label class="form-label fw-bold text-success">العميل (اختياري)</label>
                    <select name="customer_id" id="customer_select" class="form-select select2">
                        <option value="">-- اختر العميل لتوجيه القبض لحسابه --</option>
                        @foreach($customers as $c) 
                            <option value="{{ $c->id }}" {{ $transaction->model_type == 'App\Models\Customer' && $transaction->model_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option> 
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3" id="supplier_div" style="display: {{ $transaction->type == 'expense' ? 'block' : 'none' }};">
                    <label class="form-label fw-bold text-danger">المورد (اختياري)</label>
                    <select name="supplier_id" id="supplier_select" class="form-select select2">
                        <option value="">-- اختر المورد لتوجيه الصرف لحسابه --</option>
                        @foreach($suppliers as $s) 
                            <option value="{{ $s->id }}" {{ $transaction->model_type == 'App\Models\Supplier' && $transaction->model_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option> 
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">المبلغ <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" value="{{ floatval($transaction->amount) }}" step="0.01" min="1" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">تاريخ الحركة <span class="text-danger">*</span></label>
                    <input type="date" name="transaction_date" class="form-control" value="{{ $transaction->transaction_date }}" required>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label fw-bold">البيان / الملاحظات <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required>{{ $transaction->description }}</textarea>
                </div>
            </div>

            <div class="text-end border-top pt-3">
                <button type="submit" class="btn btn-warning fw-bold px-5 shadow-sm">
                    <i class="fa-solid fa-save me-1"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
    $(document).ready(function() { 
        $('.select2').select2({ theme: 'bootstrap-5', dir: 'rtl', width: '100%' }); 
    });

    function togglePartyLists() {
        let type = document.getElementById('type_select').value;
        let cDiv = document.getElementById('customer_div');
        let sDiv = document.getElementById('supplier_div');
        let cSelect = document.getElementById('customer_select');
        let sSelect = document.getElementById('supplier_select');

        if (type === 'income') {
            cDiv.style.display = 'block'; 
            sDiv.style.display = 'none';
            sSelect.value = ''; // مسح قيمة المورد
        } else {
            cDiv.style.display = 'none'; 
            sDiv.style.display = 'block';
            cSelect.value = ''; // مسح قيمة العميل
        }
    }

    // إرسال طلب التعديل
    document.getElementById('editTreasuryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        fetch("{{ route('treasury.update', $transaction->id) }}", {
            method: "PUT",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json().then(d => ({status: res.status, body: d})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire({
                    icon: 'success', title: 'تم التعديل!', text: result.body.message, timer: 1500, showConfirmButton: false
                }).then(() => window.location.href = "{{ route('treasury.index') }}");
            } else {
                Swal.fire({icon: 'error', title: 'خطأ', text: result.body.message || 'تأكد من صحة البيانات'});
            }
        });
    });
</script>
@endpush
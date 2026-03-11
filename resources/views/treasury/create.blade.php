@extends('layouts.app')
@section('title', 'إصدار سند')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">إصدار سند مالي جديد</h3>
    <a href="{{ route('treasury.index') }}" class="btn btn-secondary shadow-sm fw-bold">
        <i class="fa-solid fa-arrow-right-long me-1"></i> رجوع للخلف
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form id="treasuryForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">الخزينة <span class="text-danger">*</span></label>
                    <select name="treasury_id" class="form-select" required>
                        @foreach($treasuries as $tr) 
                            <option value="{{ $tr->id }}">{{ $tr->name }}</option> 
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-bold">نوع السند <span class="text-danger">*</span></label>
                    <select name="type" id="trans_type" class="form-select" required onchange="toggleParty()">
                        <option value="">اختر النوع...</option>
                        <option value="income">سند قبض (استلام من عميل / إيراد)</option>
                        <option value="expense">سند صرف (دفع لمورد / مصروف)</option>
                    </select>
                </div>

                <div class="col-md-6" id="customer_div" style="display: none;">
                    <label class="form-label fw-bold text-success">العميل المُسدد (اختياري)</label>
                    <select name="customer_id" id="customer_select" class="form-select select2">
                        <option value="">-- حركة عامة (بدون عميل) --</option>
                        @foreach($customers as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="supplier_div" style="display: none;">
                    <label class="form-label fw-bold text-danger">المورد المُستفيد (اختياري)</label>
                    <select name="supplier_id" id="supplier_select" class="form-select select2">
                        <option value="">-- حركة عامة (بدون مورد) --</option>
                        @foreach($suppliers as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                    </select>
                </div>

                <div class="col-md-4 mt-4">
                    <label class="form-label fw-bold">المبلغ <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control form-control-lg text-center fw-bold" step="0.01" min="1" required placeholder="0.00">
                </div>

                <div class="col-md-4 mt-4">
                    <label class="form-label fw-bold">التاريخ <span class="text-danger">*</span></label>
                    <input type="date" name="transaction_date" class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="col-md-12 mt-4">
                    <label class="form-label fw-bold">البيان / الملاحظات <span class="text-danger">*</span></label>
                    <input type="text" name="description" class="form-control" required placeholder="مثال: سداد دفعة من الحساب...">
                </div>
            </div>

            <hr class="my-4">
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-5 fw-bold btn-lg"><i class="fa-solid fa-save me-2"></i> حفظ واعتماد السند</button>
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

    // سكريبت إظهار وإخفاء القوائم بذكاء
    function toggleParty() {
        let type = document.getElementById('trans_type').value;
        let customerDiv = document.getElementById('customer_div');
        let supplierDiv = document.getElementById('supplier_div');
        let customerSelect = document.getElementById('customer_select');
        let supplierSelect = document.getElementById('supplier_select');

        // إعادة تعيين القيم لتجنب الأخطاء
        $('#customer_select').val(null).trigger('change');
        $('#supplier_select').val(null).trigger('change');

        if (type === 'income') {
            customerDiv.style.display = 'block';
            supplierDiv.style.display = 'none';
        } else if (type === 'expense') {
            customerDiv.style.display = 'none';
            supplierDiv.style.display = 'block';
        } else {
            customerDiv.style.display = 'none';
            supplierDiv.style.display = 'none';
        }
    }

    // إرسال الطلب (AJAX)
    document.getElementById('treasuryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({title: 'جاري الاعتماد...', didOpen: () => Swal.showLoading()});
        
        fetch("{{ route('treasury.store') }}", {
            method: "POST", 
            body: new FormData(this),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire('تم الاعتماد!', result.body.message, 'success').then(() => window.location.href = "{{ route('treasury.index') }}");
            } else { 
                Swal.fire('خطأ!', result.body.message, 'error'); 
            }
        }).catch(err => {
            Swal.fire('خطأ!', 'حدثت مشكلة في الاتصال.', 'error');
        });
    });
</script>
@endpush
{{-- Purchases Module: Edit existing purchase --}}
@extends('layouts.app')
@section('title', 'تعديل فاتورة شراء')
@section('content')

<h4 class="mb-4 fw-bold">تعديل الفاتورة رقم: {{ $purchase->invoice_number }}</h4>

<form id="editPurchaseForm">
    @method('PUT')
    <div class="row">
        <a href="{{ url()->previous() }}" class="btn btn-secondary shadow-sm fw-bold">
                <i class="fa-solid fa-arrow-right-long me-1"></i> رجوع للخلف
        </a>
        <div class="col-md-4 mb-3">
            <div class="card p-3 shadow-sm border-0">
                <h6 class="fw-bold mb-3">بيانات المورد</h6>
                <div class="mb-3">
                    <label>المورد</label>
                    <select name="supplier_id" class="form-select select2" required>
                        @foreach($suppliers as $s) <option value="{{ $s->id }}" {{ $purchase->supplier_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option> @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>المخزن</label>
                    <select name="warehouse_id" class="form-select" required>
                        @foreach($warehouses as $w) <option value="{{ $w->id }}" {{ $purchase->warehouse_id == $w->id ? 'selected' : '' }}>{{ $w->name }}</option> @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>التاريخ</label>
                    <input type="date" name="invoice_date" class="form-control" value="{{ $purchase->invoice_date }}" required>
                </div>
            </div>
        </div>

            <div class="table-responsive">
            <table class="table table-bordered" id="itemsTable">
                <thead class="bg-body-secondary">
                    <tr>
                        <th>الخامة</th>
                        <th>الكمية</th>
                        <th>سعر الشراء</th>
                        <th>الإجمالي</th>
                        <th class="text-center"><i class="fa-solid fa-trash"></i></th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">الإجمالي</td>
                        <td class="fw-bold text-success fs-5" id="grand_total">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
</div>
        </div>
    </div>
    <div class="text-end mt-4"><button type="submit" class="btn btn-success px-5 btn-lg">حفظ التعديلات</button></div>
</form>

@endsection

@push('js')
<script>
    const products = @json($products);
const existingItems = @json($purchase->items);

function addItem(data = null) {
    let idx = document.querySelector('#itemsTable tbody').rows.length;
    let options = '<option value="">اختر...</option>';
    products.forEach(p => {
        let sel = (data && data.product_id == p.id) ? 'selected' : '';
        options += `<option value="${p.id}" ${sel}>${p.name}</option>`;
    });

    let qty = data ? data.quantity : '';
    let price = data ? data.unit_price : '';
    // تم تغيير اسم المتغير إلى itemTotal بدلاً من total
    let itemTotal = data ? (qty * price).toFixed(2) : ''; 

    let row = `<tr>
            <td><select name="items[${idx}][product_id]" class="form-select select2-item" required>${options}</select></td>
            <td><input type="number" name="items[${idx}][quantity]" class="form-control qty" step="0.1" value="${qty}" oninput="calc(this)" required></td>
            <td><input type="number" name="items[${idx}][unit_price]" class="form-control price" step="0.1" value="${price}" oninput="calc(this)" required></td>
            <td><input type="text" class="form-control total" value="${itemTotal}" readonly></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); calculateTotal();">X</button></td>
        </tr>`;
    
    $('#itemsTable tbody').append(row);
    $('.select2-item').last().select2({ theme: 'bootstrap-5', dir: 'rtl', width: '100%' });
    
    // استدعاء الدالة بالاسم الجديد
    calculateTotal(); 
}

function calc(el) {
    let row = el.closest('tr');
    row.querySelector('.total').value = ( (parseFloat(row.querySelector('.qty').value)||0) * (parseFloat(row.querySelector('.price').value)||0) ).toFixed(2);
    // استدعاء الدالة بالاسم الجديد
    calculateTotal(); 
}

// تم تغيير اسم الدالة إلى calculateTotal لتجنب أي تعارض
function calculateTotal() {
    let sum = 0;
    document.querySelectorAll('.total').forEach(e => sum += parseFloat(e.value) || 0);
    document.getElementById('grand_total').innerText = sum.toFixed(2);
}

$(document).ready(function() { 
    $('.select2').select2({ theme: 'bootstrap-5', dir: 'rtl', width: '100%' }); 
    if(existingItems.length > 0) {
        existingItems.forEach(i => addItem(i)); 
    } else {
        addItem();
    }
});

document.getElementById('editPurchaseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    fetch("{{ route('purchases.update', $purchase->id) }}", {
        method: "POST", 
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 
            'Accept': 'application/json'
        }, 
        body: formData
    }).then(res => res.json().then(d => {
        if(res.status === 200) {
            Swal.fire('تم', d.message, 'success').then(()=> window.location.href="{{ route('purchases.index') }}");
        } else {
            Swal.fire('خطأ', d.message, 'error');
        }
    }));
});
</script>
@endpush
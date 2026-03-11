<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-bold">المورد <span class="text-danger">*</span></label>
        <select name="supplier_id" class="form-select select2" required>
            <option value="">اختر المورد...</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ (isset($invoice) && $invoice->supplier_id == $s->id) ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold">المخزن المستلم <span class="text-danger">*</span></label>
        <select name="warehouse_id" class="form-select" required>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" {{ (isset($invoice) && $invoice->warehouse_id == $w->id) ? 'selected' : '' }}>{{ $w->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold">تاريخ الفاتورة <span class="text-danger">*</span></label>
        <input type="date" name="invoice_date" class="form-control" value="{{ $invoice->invoice_date ?? date('Y-m-d') }}" required>
    </div>
</div>

<div class="card mt-4 shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">أصناف الفاتورة</h6>
        <button type="button" class="btn btn-sm btn-primary" onclick="addItem()"><i class="fa-solid fa-plus"></i> إضافة صنف</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0" id="itemsTable">
            <thead class="bg-light">
                <tr>
                    <th width="40%">المادة الخام</th>
                    <th width="15%">الكمية</th>
                    <th width="20%">سعر الشراء الوحدة</th>
                    <th width="15%">الإجمالي</th>
                    <th width="10%"></th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-bold">إجمالي الفاتورة</td>
                    <td class="fw-bold text-danger fs-5" id="grand_total">0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="mt-3">
    <label class="form-label fw-bold">ملاحظات</label>
    <textarea name="notes" class="form-control" rows="2">{{ $invoice->notes ?? '' }}</textarea>
</div>

<hr class="my-4">
<div class="text-end">
    <a href="{{ route('purchases.index') }}" class="btn btn-secondary px-4 me-2">إلغاء</a>
    <button type="submit" class="btn btn-success px-5 btn-lg"><i class="fa-solid fa-save me-2"></i> حفظ الفاتورة</button>
</div>

@push('js')
<script>
    const products = @json($products);
    const existingItems = @json($invoice->items ?? []);
    let rowIdx = 0; // العداد العالمي لتجنب تداخل الـ ID عند الحذف

    function addItem(data = null) {
        let options = '<option value="">اختر المادة...</option>';
        products.forEach(p => {
            let selected = (data && data.product_id == p.id) ? 'selected' : '';
            options += `<option value="${p.id}" ${selected}>[${p.sku}] ${p.name}</option>`;
        });

        let qty = data ? data.quantity : '';
        let price = data ? data.unit_price : '';
        let total = data ? (data.quantity * data.unit_price).toFixed(2) : '';

        let html = `
            <tr>
                <td><select name="items[${rowIdx}][product_id]" class="form-select select2-item" required>${options}</select></td>
                <td><input type="number" name="items[${rowIdx}][quantity]" class="form-control qty" step="0.1" value="${qty}" oninput="calcRow(this)" required></td>
                <td><input type="number" name="items[${rowIdx}][unit_price]" class="form-control price" step="0.01" value="${price}" oninput="calcRow(this)" required></td>
                <td><input type="text" class="form-control row-total" value="${total}" readonly></td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fa-solid fa-xmark"></i></button></td>
            </tr>`;
        
        $('#itemsTable tbody').append(html);
        $('.select2-item').last().select2({ theme: 'bootstrap-5', dir: 'rtl', width: '100%' });
        rowIdx++;
        calcTotal();
    }

    function removeRow(btn) { btn.closest('tr').remove(); calcTotal(); }
    
    function calcRow(el) {
        let row = el.closest('tr');
        let qty = parseFloat(row.querySelector('.qty').value) || 0;
        let price = parseFloat(row.querySelector('.price').value) || 0;
        row.querySelector('.row-total').value = (qty * price).toFixed(2);
        calcTotal();
    }

    function calcTotal() {
        let grand = 0;
        document.querySelectorAll('.row-total').forEach(el => grand += parseFloat(el.value) || 0);
        document.getElementById('grand_total').innerText = grand.toFixed(2);
    }

    $(document).ready(function() {
        $('.select2').select2({ theme: 'bootstrap-5', dir: 'rtl', width: '100%' });
        if(existingItems.length > 0) {
            existingItems.forEach(item => addItem(item));
        } else {
            addItem();
        }
    });

    // سكريبت الحفظ (AJAX) - يتم استدعاؤه من الـ Form في صفحة Create/Edit
</script>
@endpush
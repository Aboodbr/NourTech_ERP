@extends('layouts.app')
@section('title', 'إنشاء مرتجع جديد')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-file-invoice me-2"></i> إنشاء مرتجع جديد</h3>
    <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary fw-bold shadow-sm">
        <i class="fa-solid fa-arrow-right me-1"></i> رجوع
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form id="fetchInvoiceForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">نوع المرتجع</label>
                <select name="type" id="return_type" class="form-select" required>
                    <option value="">-- اختر --</option>
                    <option value="sales_return">مرتجع مبيعات (عميل)</option>
                    <option value="purchase_return">مرتجع مشتريات (مورد)</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">رقم الفاتورة الأصلية</label>
                <input type="text" name="invoice_id" id="invoice_id" class="form-control" placeholder="INV-001 أو PUR-001..." required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-dark w-100 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> جلب أصناف الفاتورة
                </button>
            </div>
        </form>
    </div>
</div>

<form id="saveReturnForm" class="d-none">
    <input type="hidden" name="type" id="form_type">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white pb-0 border-0">
            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-info-circle me-2"></i> البيانات الأساسية</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3" id="customer_container">
                    <label class="form-label fw-bold">العميل</label>
                    <select name="customer_id" id="customer_id" class="form-select select2">
                        <option value="">-- اختر العميل --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3 d-none" id="supplier_container">
                    <label class="form-label fw-bold">المورد</label>
                    <select name="supplier_id" id="supplier_id" class="form-select select2">
                        <option value="">-- اختر المورد --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">تاريخ المرتجع</label>
                    <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">المخزن المتأثر</label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                        <option value="">-- اختر المخزن --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">تسوية مالية من/إلى الخزينة؟ (اختياري)</label>
                    <select name="treasury_id" class="form-select select2">
                        <option value="">-- اترك فارغاً للآجل --</option>
                        @foreach($treasuries as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">سبب المرتجع / ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white pb-0 border-0">
            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-list me-2"></i> أصناف المرتجع</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>تحديد</th>
                            <th>الصنف</th>
                            <th>الكمية المرتجعة</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody id="items_tbody">
                        <!-- Items will be injected here -->
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold">الإجمالي الكلي:</td>
                            <td class="fw-bold text-danger fs-5" id="grand_total">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-end py-3">
            <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm" id="btn_save" disabled>
                <i class="fa-solid fa-save me-1"></i> حفظ كمسودة
            </button>
        </div>
    </div>
</form>

@endsection

@push('js')
<script>
    document.getElementById('fetchInvoiceForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const type = document.getElementById('return_type').value;
        const invoice_id = document.getElementById('invoice_id').value;

        Swal.fire({title: 'جاري البحث...', didOpen: () => Swal.showLoading()});

        fetch(`/returns/invoice-items?type=${type}&invoice_id=${invoice_id}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.close();
                let data = result.body;

                // Show form
                document.getElementById('saveReturnForm').classList.remove('d-none');
                document.getElementById('form_type').value = type;

                // Toggle Customer/Supplier
                if(type === 'sales_return') {
                    document.getElementById('customer_container').classList.remove('d-none');
                    document.getElementById('supplier_container').classList.add('d-none');
                    $('#customer_id').val(data.model_id).trigger('change');
                } else {
                    document.getElementById('supplier_container').classList.remove('d-none');
                    document.getElementById('customer_container').classList.add('d-none');
                    $('#supplier_id').val(data.model_id).trigger('change');
                }

                $('#warehouse_id').val(data.warehouse_id).trigger('change');

                // Render Items
                let tbody = '';
                data.items.forEach((item, index) => {
                    let productName = item.product ? item.product.name : 'محذوف';
                    tbody += `
                        <tr>
                            <td>
                                <input class="form-check-input select-item-chk" type="checkbox" onchange="toggleItem(${index})">
                                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}" disabled class="item-input item-product">
                            </td>
                            <td>${productName} (الكمية الأصلية: ${item.quantity})</td>
                            <td>
                                <input type="number" name="items[${index}][quantity]" class="form-control item-input item-qty" max="${item.quantity}" value="${item.quantity}" oninput="calcTotal()" disabled>
                            </td>
                            <td>
                                <input type="number" name="items[${index}][unit_price]" class="form-control item-input item-price" value="${item.unit_price}" oninput="calcTotal()" disabled>
                            </td>
                            <td class="fw-bold item-total">${(item.quantity * item.unit_price).toFixed(2)}</td>
                        </tr>
                    `;
                });

                document.getElementById('items_tbody').innerHTML = tbody;
                calcTotal();

            } else {
                Swal.fire('خطأ', result.body.message, 'error');
            }
        });
    });

    window.toggleItem = function(index) {
        let tr = document.getElementById('items_tbody').children[index];
        let chk = tr.querySelector('.select-item-chk');
        let inputs = tr.querySelectorAll('.item-input');

        inputs.forEach(inp => {
            inp.disabled = !chk.checked;
        });

        calcTotal();
    };

    window.calcTotal = function() {
        let total = 0;
        let checkedCount = 0;

        document.querySelectorAll('#items_tbody tr').forEach(tr => {
            let chk = tr.querySelector('.select-item-chk');
            if(chk.checked) {
                checkedCount++;
                let qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
                let price = parseFloat(tr.querySelector('.item-price').value) || 0;
                let lineTotal = qty * price;
                tr.querySelector('.item-total').innerText = lineTotal.toFixed(2);
                total += lineTotal;
            } else {
                tr.querySelector('.item-total').innerText = '0.00';
            }
        });

        document.getElementById('grand_total').innerText = total.toFixed(2);
        document.getElementById('btn_save').disabled = (checkedCount === 0);
    };

    document.getElementById('saveReturnForm').addEventListener('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        Swal.fire({title: 'جاري الحفظ...', didOpen: () => Swal.showLoading()});

        fetch("{{ route('returns.store') }}", {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json().then(data => ({status: res.status, body: data})))
        .then(result => {
            if(result.status === 200) {
                Swal.fire('تم!', result.body.message, 'success').then(() => {
                    window.location.href = "{{ route('returns.index') }}";
                });
            } else {
                Swal.fire('خطأ!', result.body.message, 'error');
            }
        });
    });
</script>
@endpush
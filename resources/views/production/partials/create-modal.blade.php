<div class="modal fade" id="createOrderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-clipboard-list me-2"></i> أمر شغل جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="createOrderForm">
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">المنتج المراد تصنيعه</label>
                        <select name="product_id" id="prod_product_select" class="form-select" required>
                            <option value="" selected disabled>اختر المنتج...</option>
                            @foreach($productsList as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">* تظهر هنا فقط المنتجات التي لها معادلة تصنيع.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small">مخزن الصرف والاستلام</label>
                        <select name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold small">الكمية المطلوبة</label>
                            <input type="number" name="quantity" class="form-control" required min="1" step="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold small">تاريخ الإنتاج</label>
                            <input type="date" name="production_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ الأمر</button>
                </div>
            </form>
        </div>
    </div>
</div>
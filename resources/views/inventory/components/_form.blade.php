@if ($errors->any())
    <div class="alert alert-danger shadow-sm border-0 mb-4">
        <strong class="d-block mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> يرجى تصحيح الأخطاء التالية:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger shadow-sm border-0 mb-4">
        <strong><i class="fa-solid fa-database me-1"></i> خطأ برمجي:</strong> {{ session('error') }}
    </div>
@endif

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-bold">كود المادة (SKU) <span class="text-danger">*</span></label>
        <input type="text" name="sku" class="form-control" value="{{ old('sku', $item->sku ?? '') }}" required placeholder="مثال: RAW-101">
    </div>

    <div class="col-md-8">
        <label class="form-label fw-bold">اسم المادة / الصنف <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $item->name ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold">النوع <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
            <option value="raw_material" {{ old('type', optional($item->type)->value ?? $item->type) == 'raw_material' ? 'selected' : '' }}>مادة خام</option>
            <option value="finished_good" {{ old('type', optional($item->type)->value ?? $item->type) == 'finished_good' ? 'selected' : '' }}>منتج تام</option>
            
            {{-- الإضافة الجديدة للصنف الخدمي --}}
            <option value="service" {{ old('type', optional($item->type)->value ?? $item->type) == 'service' ? 'selected' : '' }}>صنف خدمي / غير مخزني</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold">وحدة القياس <span class="text-danger">*</span></label>
        <select name="unit" class="form-select" required>
            <option value="">اختر الوحدة...</option>
            <option value="قطعة" {{ old('unit', $item->unit ?? '') == 'قطعة' ? 'selected' : '' }}>قطعة (Piece)</option>
            <option value="كجم" {{ old('unit', $item->unit ?? '') == 'كجم' ? 'selected' : '' }}>كيلوجرام (KG)</option>
            <option value="متر" {{ old('unit', $item->unit ?? '') == 'متر' ? 'selected' : '' }}>متر (Meter)</option>
            <option value="لتر" {{ old('unit', $item->unit ?? '') == 'لتر' ? 'selected' : '' }}>لتر (Liter)</option>
            <option value="طقم" {{ old('unit', $item->unit ?? '') == 'طقم' ? 'selected' : '' }}>طقم (Set)</option>
            <option value="كيس" {{ old('unit', $item->unit ?? '') == 'كيس' ? 'selected' : '' }}>كيس (Bag)</option>
            
            {{-- الإضافة الجديدة لوحدة الخدمة --}}
            <option value="خدمة" {{ old('unit', $item->unit ?? '') == 'خدمة' ? 'selected' : '' }}>خدمة (Service)</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold text-danger">حد التنبيه (النواقص) <span class="text-danger">*</span></label>
        <input type="number" step="0.1" name="min_stock" class="form-control border-danger" value="{{ old('min_stock', $item->min_stock ?? 0) }}" required>
    </div>
</div>

<hr class="my-4">
<div class="text-end">
    <a href="{{ route('inventory.index') }}" class="btn btn-secondary px-4 me-2">إلغاء والرجوع</a>
    <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="fa-solid fa-save me-2"></i> حفظ البيانات</button>
</div>
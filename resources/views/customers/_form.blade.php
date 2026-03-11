@if ($errors->any())
    <div class="alert alert-danger shadow-sm border-0 mb-4">
        <strong class="d-block mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> يرجى تصحيح الأخطاء:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-bold">اسم العميل <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
    </div>
    
    <div class="col-md-6">
        <label class="form-label fw-bold">رقم الهاتف</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" dir="ltr">
    </div>
    
    <div class="col-md-12">
        <label class="form-label fw-bold">العنوان</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}">
    </div>
</div>

<hr class="my-4">
<div class="text-end">
    <a href="{{ url()->previous() }}" class="btn btn-secondary px-4 me-2">إلغاء والرجوع</a>
    <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="fa-solid fa-save me-2"></i> حفظ بيانات العميل</button>
</div>
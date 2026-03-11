@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-4 text-primary"><i class="fas fa-cogs"></i> إعدادات النظام</h2>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-sliders-h"></i> الإعدادات العامة</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="company_name" class="form-label">اسم الشركة</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name', $setting->company_name) }}" required>
                        @error('company_name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="currency" class="form-label">العملة الافتراضية</label>
                        <input type="text" class="form-control" id="currency" name="currency" value="{{ old('currency', $setting->currency) }}" placeholder="مثال: ر.س, USD">
                        @error('currency') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="timezone" class="form-label">المنطقة الزمنية</label>
                        <select class="form-select" id="timezone" name="timezone">
                            <option value="Asia/Riyadh" {{ old('timezone', $setting->timezone) == 'Asia/Riyadh' ? 'selected' : '' }}>Asia/Riyadh (السعودية)</option>
                            <option value="Africa/Cairo" {{ old('timezone', $setting->timezone) == 'Africa/Cairo' ? 'selected' : '' }}>Africa/Cairo (مصر)</option>
                            <option value="Asia/Dubai" {{ old('timezone', $setting->timezone) == 'Asia/Dubai' ? 'selected' : '' }}>Asia/Dubai (الإمارات)</option>
                            <option value="UTC" {{ old('timezone', $setting->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                        </select>
                        @error('timezone') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="company_logo" class="form-label">شعار الشركة</label>
                        <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                        @if($setting->company_logo)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $setting->company_logo) }}" alt="Logo" class="img-thumbnail" width="100">
                            </div>
                        @endif
                        @error('company_logo') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="default_warehouse" class="form-label">المستودع الافتراضي</label>
                        <select class="form-select" id="default_warehouse" name="default_warehouse">
                            <option value="">-- اختر المستودع --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('default_warehouse', $setting->default_warehouse) == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('default_warehouse') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="default_treasury" class="form-label">الخزينة الافتراضية</label>
                        <select class="form-select" id="default_treasury" name="default_treasury">
                            <option value="">-- اختر الخزينة --</option>
                            @foreach($treasuries as $tr)
                                <option value="{{ $tr->id }}" {{ old('default_treasury', $setting->default_treasury) == $tr->id ? 'selected' : '' }}>
                                    {{ $tr->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('default_treasury') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الإعدادات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

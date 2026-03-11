@extends('layouts.app')

@section('title', 'تعديل مكونات منتج (BOM)')

@section('content')

<div class="card shadow-sm">
    <div class="card-body p-4">
        
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('bom.update', $bom->id) }}" method="POST" id="bomForm">
            @csrf
            @method('PUT')

            <h5 class="fw-bold mb-3 text-amc-red"><i class="fas fa-edit me-2"></i> تعديل بيانات المعادلة</h5>
            <div class="row mb-4 p-3 bg-light rounded-3 border border-light">
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">المنتج التام <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select select2" required>
                        <option value="">-- اختر المنتج التام --</option>
                        @foreach($finishedProducts as $product)
                            <option value="{{ $product->id }}" {{ $bom->product_id == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">اسم أو وصف المعادلة</label>
                    <input type="text" name="name" value="{{ $bom->name }}" class="form-control">
                </div>

                <div class="col-md-2 mb-3 d-flex align-items-end pb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ $bom->is_active ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold ms-2" for="isActive">معادلة مفعلة</label>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-3 text-amc-blue mt-4"><i class="fas fa-cubes me-2"></i> المواد الخام</h5>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover border-secondary border-opacity-25" id="bomItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="50%">المادة الخام</th>
                            <th width="35%">الكمية المطلوبة (للقطعة الواحدة)</th>
                            <th width="15%" class="text-center">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="bomItemsBody">
                        @foreach($bom->items as $index => $bomItem)
                            <tr>
                                <td>
                                    <select name="items[{{ $index }}][raw_material_id]" class="form-select select-raw" required>
                                        <option value="">-- اختر المادة الخام --</option>
                                        @foreach($rawMaterials as $material)
                                            <option value="{{ $material->id }}" {{ $bomItem->raw_material_id == $material->id ? 'selected' : '' }}>
                                                {{ $material->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ floatval($bomItem->quantity) }}" class="form-control" step="0.0001" min="0.0001" required>
                                        <span class="input-group-text text-muted">وحدة</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row" {{ count($bom->items) == 1 ? 'disabled' : '' }} title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="bg-light">
                                <button type="button" class="btn btn-sm btn-success fw-bold px-3" id="addRowBtn">
                                    <i class="fas fa-plus me-1"></i> إضافة مادة خام أخرى
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="text-end mt-4 pt-3 border-top">
                <a href="{{ route('bom.index') }}" class="btn btn-secondary btn-lg fw-bold px-4 me-2">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm">
                    <i class="fas fa-save me-2"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>

<div id="rawMaterialsOptions" class="d-none">
    <option value="">-- اختر المادة الخام --</option>
    @foreach($rawMaterials as $material)
        <option value="{{ $material->id }}">{{ $material->name }}</option>
    @endforeach
</div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        // تحديد رقم البداية للـ Index بناءً على عدد العناصر الحالية
        let rowCount = {{ count($bom->items) }};
        const optionsHtml = $('#rawMaterialsOptions').html();

        $('#addRowBtn').click(function() {
            let newRow = `
                <tr>
                    <td>
                        <select name="items[${rowCount}][raw_material_id]" class="form-select select-raw" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" name="items[${rowCount}][quantity]" class="form-control" step="0.0001" min="0.0001" required>
                            <span class="input-group-text text-muted">وحدة</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#bomItemsBody').append(newRow);
            rowCount++;
            updateRemoveButtons();
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            let rowLength = $('#bomItemsBody tr').length;
            if(rowLength === 1) {
                $('.remove-row').prop('disabled', true);
            } else {
                $('.remove-row').prop('disabled', false);
            }
        }
    });
</script>
@endpush
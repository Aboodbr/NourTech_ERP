@extends('layouts.app')

@section('title', 'تعريف مكونات منتج (BOM)')

@section('content')

<div class="alert alert-info border-0 border-start border-4 border-info shadow-sm mb-4 bg-white" role="alert">
    <div class="d-flex">
        <div class="fs-4 text-info me-3"><i class="fas fa-lightbulb"></i></div>
        <div>
            <h6 class="fw-bold mb-1 text-dark">ما هي هذه الشاشة؟</h6>
            <p class="mb-0 text-secondary fs-6">
                هنا نقوم بتعريف المنتج التام وما يستهلكه من مواد خام (وصفة التصنيع). 
                <strong>ملاحظة هامة:</strong> المنتجات التي يتم تعريفها هنا فقط هي التي ستظهر لاحقاً في شاشة "أوامر التصنيع".
            </p>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('bom.store') }}" method="POST" id="bomForm">
            @csrf

            <h5 class="fw-bold mb-3 text-amc-red"><i class="fas fa-box-open me-2"></i> أولاً: اختر المنتج المراد تعريفه</h5>
            <div class="row mb-4 p-3 bg-light rounded-3 border border-light">
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">المنتج التام (المنتج X) <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select select2" required>
                        <option value="">-- اختر المنتج الذي تريد تصنيعه مستقبلاً --</option>
                        @foreach($finishedProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">اسم أو وصف المعادلة (اختياري)</label>
                    <input type="text" name="name" class="form-control" placeholder="مثال: التركيبة القياسية">
                </div>

                <div class="col-md-2 mb-3 d-flex align-items-end pb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked value="1">
                        <label class="form-check-label fw-bold ms-2" for="isActive">معادلة مفعلة</label>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-3 text-amc-blue mt-4"><i class="fas fa-cubes me-2"></i> ثانياً: المواد الخام التي يستهلكها المنتج (للقطعة الواحدة)</h5>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover border-secondary border-opacity-25" id="bomItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="50%">المادة الخام (ما سيتم سحبه من المخزن)</th>
                            <th width="35%">الكمية المطلوبة (لإنتاج 1 وحدة من المنتج X)</th>
                            <th width="15%" class="text-center">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="bomItemsBody">
                        <tr>
                            <td>
                                <select name="items[0][raw_material_id]" class="form-select select-raw" required>
                                    <option value="">-- اختر المادة الخام --</option>
                                    @foreach($rawMaterials as $material)
                                        <option value="{{ $material->id }}">{{ $material->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="number" name="items[0][quantity]" class="form-control" step="0.0001" min="0.0001" required placeholder="مثال: 0.500">
                                    <span class="input-group-text text-muted">وحدة</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row" disabled title="حذف هذا الصنف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
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
                <a href="{{ route('bom.index') }}" class="btn btn-secondary btn-lg fw-bold px-4 me-2">
                    <i class="fas fa-times me-2"></i> إلغاء
                </a>
                <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm">
                    <i class="fas fa-save me-2"></i> حفظ مكونات المنتج
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
        let rowCount = 1;
        const optionsHtml = $('#rawMaterialsOptions').html();

        // إضافة صف جديد
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
                            <input type="number" name="items[${rowCount}][quantity]" class="form-control" step="0.0001" min="0.0001" required placeholder="مثال: 0.500">
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
            
            // إذا كنت تستخدم Select2 وتريد تفعيله للصفوف الجديدة، أزل التعليق عن السطر التالي:
            // $('.select-raw').last().select2({ theme: 'bootstrap-5', dir: "rtl", width: '100%' });

            rowCount++;
            updateRemoveButtons();
        });

        // حذف صف
        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            updateRemoveButtons();
        });

        // منع حذف الصف الوحيد المتبقي
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
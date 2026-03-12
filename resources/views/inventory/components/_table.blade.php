<div class="table-responsive">
    <table class="table table-hover align-middle text-center">
        <thead class="table-secondary">
            <tr>
                <th>الكود (SKU)</th>
                <th>اسم الصنف</th>
                <th>النوع</th>
                <th style="width:140px">الرصيد الكلي</th>
                <th class="text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventory as $item)
            @php
                $stock = $item->stocks_sum_quantity ?? 0;
                $isShortage = $stock <= $item->min_stock;
            @endphp
            <tr>
                <td class="fw-bold text-secondary">{{ $item->sku }}</td>

                <td class="fw-bold text-start">
                    {{ $item->name }}
                    @if($isShortage)
                        <span class="badge bg-danger ms-2">نواقص</span>
                    @endif
                </td>

                <td>
                    @if(optional($item->type)->value == 'raw_material' || $item->type == 'raw_material')
                        <span class="badge bg-secondary">مادة خام</span>
                    @else
                        <span class="badge bg-primary">منتج تام</span>
                    @endif
                </td>

                <td class="fw-bold fs-5 {{ $stock <= 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($stock, 1) }}
                </td>

                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-outline-success" title="تعديل">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteItem({{ $item->id }})" title="حذف">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-box-open fs-1 mb-3"></i><br>
                    لا توجد أصناف مسجلة أو مطابقة للبحث
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $inventory->links('pagination::bootstrap-5') }}
</div>
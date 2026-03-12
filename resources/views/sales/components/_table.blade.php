<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-secondary">
            <tr>
                <th>رقم الفاتورة</th>
                <th>العميل</th>
                <th>المخزن الصادر منه</th>
                <th>الإجمالي</th>
                <th>التاريخ</th>
                <th>الحالة</th>
                <th class="text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
            <tr>
                <td class="fw-bold">{{ $inv->invoice_number }}</td>
                <td>{{ $inv->customer->name ?? 'غير محدد' }}</td>
                <td>{{ $inv->warehouse->name ?? '-' }}</td>
                <td class="fw-bold text-success">{{ number_format($inv->total_amount, 2) }}</td>
                <td>{{ $inv->invoice_date }}</td>
                <td>
                    @if($inv->status == 'approved')
                        <span class="badge bg-success">مرحلة (نهائية)</span>
                    @else
                        <span class="badge bg-warning text-body">مسودة</span>
                    @endif
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="showInvoice({{ $inv->id }})" title="عرض التفاصيل"><i class="fa-solid fa-eye"></i></button>
                        <a href="{{ route('sales.print', $inv->id) }}" target="_blank" class="btn btn-outline-info" title="طباعة"><i class="fa-solid fa-print"></i></a>
                        
                        @if($inv->status == 'draft')
                            <a href="{{ route('sales.edit', $inv->id) }}" class="btn btn-outline-success" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                            <button class="btn btn-outline-danger" onclick="deleteInvoice({{ $inv->id }})" title="حذف"><i class="fa-solid fa-trash"></i></button>
                            <button class="btn btn-outline-warning text-body" onclick="approveInvoice({{ $inv->id }})" title="ترحيل وخصم من المخزن"><i class="fa-solid fa-check-double"></i></button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-file-invoice-dollar fs-1 mb-3"></i><br>
                    لا توجد فواتير مبيعات مسجلة
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $invoices->links('pagination::bootstrap-5') }}
</div>
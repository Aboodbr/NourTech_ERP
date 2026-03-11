<div class="card-body p-4">
    <form action="{{ route('reports.account_statement') }}" method="GET">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-bold">نوع الحساب</label>
                <select name="party_type" id="party_type" class="form-select" required onchange="togglePartyLists()">
                    <option value="">اختر...</option>
                    <option value="customer" {{ request('party_type') == 'customer' ? 'selected' : '' }}>عميل</option>
                    <option value="supplier" {{ request('party_type') == 'supplier' ? 'selected' : '' }}>مورد</option>
                </select>
            </div>

            <div class="col-md-3" id="customer_div" style="display: {{ request('party_type') == 'customer' ? 'block' : 'none' }};">
                <label class="form-label fw-bold text-success">اسم العميل</label>
                <select name="party_id" id="customer_select" class="form-select select2" {{ request('party_type') == 'customer' ? 'required' : '' }}>
                    <option value="">اختر العميل...</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('party_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3" id="supplier_div" style="display: {{ request('party_type') == 'supplier' ? 'block' : 'none' }};">
                <label class="form-label fw-bold text-danger">اسم المورد</label>
                <select name="party_id" id="supplier_select" class="form-select select2" {{ request('party_type') == 'supplier' ? 'required' : '' }}>
                    <option value="">اختر المورد...</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('party_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">من تاريخ</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date', date('Y-m-01')) }}" required>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-bold">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date', date('Y-m-d')) }}" required>
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> استخراج كشف الحساب
                </button>
            </div>
        </div>
    </form>
</div>
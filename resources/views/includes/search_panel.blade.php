<div class="card mb-4 shadow-sm border-0 d-print-none" style="background-color: #f8f9fa; border-top: 3px solid var(--amc-orange) !important;">
    <div class="card-body py-3">
        <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
            
            <div class="col-md-5">
                <label class="form-label text-secondary small fw-bold mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i> بحث عام</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="اكتب للبحث (رقم، اسم، كود)...">
            </div>

            <div class="col-md-2">
                <label class="form-label text-secondary small fw-bold mb-1">من تاريخ</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label text-secondary small fw-bold mb-1">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                    بحث
                </button>
                
                @if(request()->hasAny(['search', 'from_date', 'to_date']))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fa-solid fa-times me-1"></i> إلغاء
                    </a>
                @endif
            </div>

        </form>
    </div>
</div>
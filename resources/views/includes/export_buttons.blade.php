<div class="dropdown d-inline-block ms-2">
    <button class="btn btn-dark btn-sm dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-file-export me-1"></i> تصدير القائمة
    </button>
    <ul class="dropdown-menu shadow-sm border-0">
        <li>
            <a class="dropdown-item text-danger fw-bold" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank">
                <i class="fa-solid fa-file-pdf me-2"></i> تصدير PDF
            </a>
        </li>
        <li>
            <a class="dropdown-item text-success fw-bold" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                <i class="fa-solid fa-file-excel me-2"></i> تصدير Excel
            </a>
        </li>
    </ul>
</div>
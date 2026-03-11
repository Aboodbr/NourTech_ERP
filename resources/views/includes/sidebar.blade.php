<div class="bg-dark border-start sidebar shadow-lg" id="sidebar-wrapper">
    
    <div class="sidebar-heading text-center py-4">
        <div class="d-inline-block bg-white rounded-circle p-1 mb-2 border border-3 border-warning shadow-sm">
            <img src="{{ asset('logo.JPG') }}" alt="AMC Logo" width="70" height="70" class="rounded-circle" style="object-fit: cover;">
        </div>
        <h5 class="fw-bold mb-0 text-white" style="letter-spacing: 1px;">NourTech ERP</h5>
        <small class="text-warning fw-bold">AMC FACTORY</small>
    </div>

    <div class="list-group list-group-flush my-2 px-2 pb-5">
        
        <a href="{{ route('dashboard') ?? '#' }}" class="list-group-item list-group-item-action {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-chart-pie me-2"></i> لوحة التحكم
        </a>

        <div class="text-uppercase small fw-bold text-secondary mt-4 mb-2 px-3" style="font-size: 11px; letter-spacing: 0.5px;">العمليات التشغيلية</div>

        <a href="{{ route('inventory.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('inventory.*', 'products.*') ? 'active' : '' }}">
            <i class="fas fa-boxes-stacked me-2"></i> إدارة المخازن
        </a>
        
        <a href="{{ route('purchases.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
            <i class="fas fa-truck-ramp-box me-2"></i> المشتريات
        </a>

        <a href="{{ route('production.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('production.*') ? 'active' : '' }}">
            <i class="fas fa-industry me-2"></i> التصنيع
        </a>
        <a href="{{ route('bom.index') ?? '#' }}" class="list-group-item list-group-item-action {{ request()->routeIs('bom.*') ? 'active' : '' }}">
            <i class="fas fa-sitemap me-2"></i> تركيبة المنتجات (BOM)
        </a>
        
        <a href="{{ route('sales.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('sales.*') ? 'active' : '' }}">
            <i class="fas fa-cash-register me-2"></i> المبيعات
        </a>

        <div class="text-uppercase small fw-bold text-secondary mt-4 mb-2 px-3" style="font-size: 11px; letter-spacing: 0.5px;">المالية والتقارير</div>

        <a href="{{ route('treasury.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('treasury.*') ? 'active' : '' }}">
            <i class="fas fa-vault me-2"></i> الخزينة والحسابات
        </a>

        <a href="{{ route('reports.account_statement') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.account_statement') ? 'active' : '' }}">
            <i class="fas fa-file-invoice-dollar me-2"></i> كشف حساب
        </a>

        <a href="{{ route('reports.item_movement') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.item_movement') ? 'active' : '' }}">
            <i class="fas fa-arrow-right-arrow-left me-2"></i> حركة صنف
        </a>
        
        <a href="{{ route('reports.shortages') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.shortages') ? 'active' : '' }}">
            <i class="fas fa-triangle-exclamation me-2"></i> النواقص
        </a>

        <div class="text-uppercase small fw-bold text-secondary mt-4 mb-2 px-3" style="font-size: 11px; letter-spacing: 0.5px;">النظام</div>

        <a href="#" class="list-group-item list-group-item-action">
            <i class="fas fa-users-cog me-2"></i> الإعدادات والصلاحيات
        </a>

        <div class="mt-3 border-top border-secondary pt-3">
             <form action="#" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="list-group-item list-group-item-action text-danger fw-bold bg-transparent">
                    <i class="fas fa-power-off me-2"></i> تسجيل الخروج
                </button>
            </form>
        </div>

    </div>
</div>
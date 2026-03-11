<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%");
        }
        $suppliers = $query->latest()->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);
        Supplier::create($validated);
        Cache::forget('suppliers_list'); // مسح الكاش لكي يظهر المورد الجديد في المشتريات

        return redirect()->route('suppliers.index')->with('success', 'تم إضافة المورد');
    }

    /**
     * عرض شاشة تعديل المورد
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * حفظ التعديلات في قاعدة البيانات
     */
    public function update(Request $request, Supplier $supplier)
    {
        // 1. التحقق من صحة البيانات
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        // 2. تحديث بيانات المورد
        $supplier->update($validated);

        // 3. مسح الكاش (خطوة هامة جداً لكي يتحدث اسم المورد في القائمة المنسدلة في الفواتير)
        Cache::forget('suppliers_list');

        // 4. التوجيه لصفحة قائمة الموردين مع رسالة نجاح
        return redirect()->route('suppliers.index')->with('success', 'تم تعديل بيانات المورد بنجاح');
    }

    public function destroy(Supplier $supplier)
    {
        // منع حذف مورد له فواتير مسجلة (لحماية الداتا بيز)
        if ($supplier->purchaseInvoices()->exists()) {
            return redirect()->route('suppliers.index')->with('error', 'لا يمكن حذف هذا المورد لارتباطه بفواتير مشتريات سابقة!');
        }

        $supplier->delete();
        Cache::forget('suppliers_list');

        return redirect()->route('suppliers.index')->with('success', 'تم حذف المورد بنجاح');
    }
}

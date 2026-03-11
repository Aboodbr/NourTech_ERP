<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%");
        }
        $customers = $query->latest()->paginate(20);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);
        Customer::create($validated);
        Cache::forget('customers_list'); // مسح الكاش

        return redirect()->route('customers.index')->with('success', 'تم إضافة العميل بنجاح');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);
        $customer->update($validated);
        Cache::forget('customers_list');

        return redirect()->route('customers.index')->with('success', 'تم التعديل بنجاح');
    }

    public function destroy(Customer $customer)
    {
        // حماية قاعدة البيانات
        if ($customer->invoices()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف عميل له فواتير مسجلة!'], 403);
        }

        $customer->delete();
        Cache::forget('customers_list'); // تحديث الكاش

        return response()->json(['message' => 'تم حذف العميل بنجاح'], 200);
    }
}

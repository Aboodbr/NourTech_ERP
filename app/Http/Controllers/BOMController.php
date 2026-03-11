<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BomController extends Controller
{
    public function index()
    {
        // جلب المعادلات مع اسم المنتج التام
        $boms = BillOfMaterial::with('product')->paginate(15);

        return view('production.bom.index', compact('boms'));
    }

    public function create()
    {
        // 🔴 التعديل الجوهري هنا: استخدام الـ Enum الخاص بك
        // استخدمنا whereIn تحسباً لوجود خطأ إملائي في قاعدة البيانات (finshed بدلاً من finished)
        $finishedProducts = Product::where('type', 'finished_good')->get();

        $rawMaterials = Product::where('type', 'raw_material')->get();

        return view('production.bom.create', compact('finishedProducts', 'rawMaterials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id|unique:bill_of_materials,product_id',
            'name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001', // لأنك تستخدم 4 أرقام عشرية (15,4)
        ]);

        try {
            DB::transaction(function () use ($request) {
                // 1. إنشاء رأس المعادلة
                $bom = BillOfMaterial::create([
                    'product_id' => $request->product_id,
                    'name' => $request->name,
                    'is_active' => $request->has('is_active'),
                ]);

                // 2. إضافة المواد الخام (Items)
                foreach ($request->items as $item) {
                    $bom->items()->create([
                        'raw_material_id' => $item['raw_material_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            });

            return redirect()->route('bom.index')->with('success', 'تم حفظ معادلة التصنيع بنجاح.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء الحفظ: '.$e->getMessage());
        }
    }

    /**
     * عرض تفاصيل معادلة التصنيع (Show)
     */
    public function show($id)
    {
        // جلب المعادلة مع المنتج التام وتفاصيل المواد الخام
        $bom = BillOfMaterial::with(['product', 'items.rawMaterial'])->findOrFail($id);

        return view('production.bom.show', compact('bom'));
    }

    /**
     * فتح صفحة تعديل المعادلة (Edit)
     */
    public function edit($id)
    {
        $bom = BillOfMaterial::with('items')->findOrFail($id);

        $finishedProducts = Product::whereIn('type', ['finished_good', 'finshed_good'])->get();
        $rawMaterials = Product::where('type', 'raw_material')->get();

        return view('production.bom.edit', compact('bom', 'finishedProducts', 'rawMaterials'));
    }

    /**
     * حفظ التعديلات في قاعدة البيانات (Update)
     */
    public function update(Request $request, $id)
    {
        $bom = BillOfMaterial::findOrFail($id);

        $request->validate([
            // استثناء الـ ID الحالي من شرط عدم التكرار
            'product_id' => 'required|exists:products,id|unique:bill_of_materials,product_id,'.$bom->id,
            'name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::transaction(function () use ($request, $bom) {
                // 1. تحديث رأس المعادلة
                $bom->update([
                    'product_id' => $request->product_id,
                    'name' => $request->name,
                    'is_active' => $request->has('is_active'),
                ]);

                // 2. مسح المواد الخام القديمة بالكامل وإدخال الجديدة (أفضل وأضمن طريقة للتعديل الديناميكي)
                $bom->items()->delete();

                foreach ($request->items as $item) {
                    $bom->items()->create([
                        'raw_material_id' => $item['raw_material_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            });

            return redirect()->route('bom.index')->with('success', 'تم تعديل معادلة التصنيع بنجاح.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء التعديل: '.$e->getMessage());
        }
    }

    /**
     * حذف معادلة التصنيع (Destroy)
     */
    public function destroy($id)
    {
        try {
            $bom = BillOfMaterial::findOrFail($id);
            // سيتم حذف التفاصيل (items) تلقائياً بفضل cascadeOnDelete في الداتابيز
            $bom->delete();

            return redirect()->route('bom.index')->with('success', 'تم حذف معادلة التصنيع بنجاح.');
        } catch (Exception $e) {
            return back()->with('error', 'لا يمكن الحذف لوجود عمليات مرتبطة بهذه المعادلة.');
        }
    }
}

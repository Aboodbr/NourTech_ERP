<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء مستخدم (Admin)
        $user = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@nourtech.com',
            'password' => Hash::make('password'), // كلمة السر
        ]);

        // 2. إنشاء المخازن
        $rawWarehouse = Warehouse::create([
            'name' => 'مخزن الخامات الرئيسي',
            'location' => 'عنبر أ - بجوار بوابة 1',
        ]);

        $finishedWarehouse = Warehouse::create([
            'name' => 'مخزن المنتج التام',
            'location' => 'عنبر ج - منطقة الشحن',
        ]);

        // 3. إنشاء المنتجات (المواد الخام)
        $products = [
            [
                'name' => 'لوح صاج ستانلس 0.7 مم',
                'sku' => 'RM-ST-001',
                'type' => ProductType::RAW_MATERIAL,
                'unit' => 'لوح',
                'min_stock' => 100,
            ],
            [
                'name' => 'محبس غاز نحاس',
                'sku' => 'RM-VLV-500',
                'type' => ProductType::RAW_MATERIAL,
                'unit' => 'قطعة',
                'min_stock' => 500,
            ],
            [
                'name' => 'لوح زجاج حراري للفرن',
                'sku' => 'RM-GLS-200',
                'type' => ProductType::RAW_MATERIAL,
                'unit' => 'قطعة',
                'min_stock' => 50,
            ],
            [
                'name' => 'صوف حراري عازل',
                'sku' => 'RM-INS-100',
                'type' => ProductType::RAW_MATERIAL,
                'unit' => 'رول',
                'min_stock' => 20,
            ],
        ];

        foreach ($products as $p) {
            $product = Product::create($p);

            // إضافة رصيد افتتاحي للخامات (عشان نقدر نجرب الصرف منها)
            $initialQty = rand(500, 1000);

            // أ. إنشاء سجل الرصيد
            $stock = Stock::create([
                'product_id' => $product->id,
                'warehouse_id' => $rawWarehouse->id,
                'quantity' => $initialQty,
            ]);

            // ب. تسجيل الحركة (عشان الهيستوري يكون مظبوط)
            InventoryTransaction::create([
                'stock_id' => $stock->id,
                'type' => TransactionType::ADJUSTMENT, // تسوية / رصيد افتتاحي
                'quantity' => $initialQty,
                'user_id' => $user->id,
                'notes' => 'رصيد افتتاحي عند بداية التشغيل',
            ]);
        }

        // 4. إنشاء المنتجات (المنتج التام - البوتاجازات)
        $finishedGoods = [
            [
                'name' => 'بوتاجاز 5 شعلة بروفيشنال',
                'sku' => 'FG-5B-PRO',
                'type' => ProductType::FINISHED_GOOD,
                'unit' => 'جهاز',
                'min_stock' => 10,
            ],
            [
                'name' => 'بوتاجاز 4 شعلة ستانلس',
                'sku' => 'FG-4B-ECO',
                'type' => ProductType::FINISHED_GOOD,
                'unit' => 'جهاز',
                'min_stock' => 15,
            ],
        ];

        foreach ($finishedGoods as $p) {
            Product::create($p);
            // لن نضيف رصيد للمنتج التام، سنقوم بتصنيعه لاحقاً
        }
        \App\Models\Treasury::create([
            'name' => 'الخزنة الرئيسية',
            'balance' => 0, // رصيد افتتاحي
        ]);
    }
}
use App\Models\BillOfMaterial;
use App\Models\BillOfMaterialItem;

// ... داخل دالة run وبعد إنشاء المنتجات ...

// 1. جلب المنتجات التي أنشأناها
$stove = Product::where('sku', 'FG-5B-PRO')->first();
$steel = Product::where('sku', 'RM-ST-001')->first();
$valve = Product::where('sku', 'RM-VLV-500')->first();

if ($stove && $steel && $valve) {
    // 2. إنشاء رأس المعادلة للبوتاجاز
    $bom = BillOfMaterial::create([
        'product_id' => $stove->id,
        'name' => 'معادلة تصنيع بوتاجاز 5 شعلة القياسية',
    ]);

    // 3. إضافة المكونات
    // يحتاج 2 لوح صاج
    BillOfMaterialItem::create([
        'bill_of_material_id' => $bom->id,
        'raw_material_id' => $steel->id,
        'quantity' => 2,
    ]);

    // يحتاج 5 محابس
    BillOfMaterialItem::create([
        'bill_of_material_id' => $bom->id,
        'raw_material_id' => $valve->id,
        'quantity' => 5,
    ]);
}

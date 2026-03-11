<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. رأس معادلة التصنيع (مثلاً: معادلة بوتاجاز 5 شعلة بروفيشنال)
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->unique(); // المنتج التام المراد تصنيعه
            $table->string('name')->nullable(); // وصف اختياري
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. تفاصيل المعادلة (المكونات: صاج، زجاج، مسامير...)
        Schema::create('bill_of_material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('products'); // المادة الخام
            $table->decimal('quantity', 15, 4); // الكمية المطلوبة للقطعة الواحدة
            $table->timestamps();
        });

        // 3. أوامر التصنيع (Production Orders)
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // PO-2024-001
            $table->foreignId('product_id')->constrained(); // المنتج المطلوب إنتاجه
            $table->foreignId('warehouse_id')->constrained(); // المخزن (للصرف والإضافة)
            $table->decimal('quantity', 15, 2); // العدد المطلوب
            $table->string('status')->default('planned'); // planned, completed
            $table->date('production_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bill_of_material_items');
        Schema::dropIfExists('bill_of_materials');
    }
};

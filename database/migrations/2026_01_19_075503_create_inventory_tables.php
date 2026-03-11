<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. المخازن (أماكن التخزين)
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // مخزن الخامات الرئيسي، مخزن الإنتاج التام
            $table->string('location')->nullable();
            $table->timestamps();
        });

        // 2. المنتجات (يشمل الخام والتام)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique(); // كود الصنف
            $table->string('type'); // Enum: raw or finished
            $table->string('unit'); // kg, piece
            $table->decimal('min_stock', 10, 2)->default(0); // حد الطلب
            $table->timestamps();
        });

        // 3. الأرصدة (الكمية الحالية لكل منتج في كل مخزن)
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->unique(['product_id', 'warehouse_id']); // لا يتكرر المنتج في نفس المخزن
            $table->timestamps();
        });

        // 4. سجل الحركات (تاريخ العمليات)
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->string('type'); // Enum: purchase, sale...
            $table->decimal('quantity', 15, 2); // الكمية (+ أو -)
            $table->foreignId('user_id')->nullable()->constrained(); // من قام بالحركة
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('warehouses');
    }
};

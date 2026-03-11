<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. العملاء
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم العميل / الشركة
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->decimal('balance', 15, 2)->default(0); // رصيد العميل (مديونية)
            $table->timestamps();
        });

        // 2. رأس الفاتورة
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // INV-2024-001
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('warehouse_id')->constrained(); // المخزن الذي سيتم الصرف منه
            $table->date('invoice_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft (مسودة), approved (معتمدة/مرحلة)
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. تفاصيل الفاتورة (الأصناف)
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(); // المنتج المباع
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2); // سعر البيع
            $table->decimal('total_price', 15, 2); // الكمية * السعر
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');
    }
};

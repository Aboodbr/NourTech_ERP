<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول الخزنات والبنوك
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // الخزنة الرئيسية، البنك الأهلي...
            $table->decimal('balance', 15, 2)->default(0); // الرصيد الحالي
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. جدول المعاملات المالية (سندات القبض والصرف)
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained(); // الخزنة المتأثرة
            $table->decimal('amount', 15, 2); // المبلغ
            $table->string('type'); // income (قبض), expense (صرف)
            $table->date('transaction_date');

            // الربط مع الجهة (عميل - مورد - أو لا شيء للمصروفات العامة)
            $table->nullableMorphs('model');
            // هذا سينشئ عمودين: model_type (App\Models\Customer) و model_id

            $table->text('description')->nullable(); // البيان / ملاحظات
            $table->foreignId('user_id')->constrained(); // الموظف الذي سجل الحركة
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('treasuries');
    }
};

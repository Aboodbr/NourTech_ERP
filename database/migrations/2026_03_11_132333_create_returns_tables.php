<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('return_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // sales_return, purchase_return
            $table->date('return_date');
            $table->string('model_type'); // Customer, Supplier
            $table->unsignedBigInteger('model_id');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, approved
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('return_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_transaction_id')->constrained('return_transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_transaction_items');
        Schema::dropIfExists('return_transactions');
    }
};

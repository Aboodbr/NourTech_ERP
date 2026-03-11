<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('supplier_id'); // لتسريع جلب فواتير مورد معين
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('name');
            $table->index('phone');
        });
    }
};

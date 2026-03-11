<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('customer_id'); // لتسريع جلب فواتير عميل معين
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->index('name');
            $table->index('phone');
        });
    }
};

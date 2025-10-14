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
        Schema::create('tbl_suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table-foreignId('supplier_no')->unique();
            $table->string('supplier_name')->unique();
            $table->string('email')->unique();
            $table->string('contact_no')->nullable();
            $table->string('address')->nullable();
            $table->string('tin')->nullable();
            $table->string('vat_no')->nullable();
            $table->string('created_at');
            $table->string('updated_by')->nullable();
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_suppliers');
    }
};

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
        Schema::create('tbl_stocks', function (Blueprint $table) {
            $table->id('id');
            $table->string('itemCode')->unique();
            $table->string('product_name');
            $table->string('descriptions')->nullable();
            $table->integer('quantity_onhand')->default(0);
            $able->integer('quantity_inStock')->default(0);
            $table->decimal('unitCost', 10,2);
            $table->string('product_Type')->nullable();
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
        Schema::dropIfExists('tbl_stocks');
    }
};

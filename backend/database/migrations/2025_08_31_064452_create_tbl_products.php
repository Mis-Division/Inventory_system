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
        Schema::create('tbl_products', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->onDelete('cascade');
            $table->foreignId('supplier_id')->onDelete('cascade');
            $table->integer('quantity_inStock')->default(0);
            $table->decimal('unitPrice', 10, 2);
            $table->enum('product_type', ['Line_hardware', 'Special_Equipments', 'None'])->default('Line_hardware');
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
        Schema::dropIfExists('tbl_products');
    }
};

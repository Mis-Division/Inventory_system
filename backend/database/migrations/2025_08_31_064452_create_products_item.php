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
        Schema::create('tbl_products_item', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('category_id')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->enum('unit', ['pcs', 'box', 'kg'])->default('pcs');
            $table->enum('product_type', ['Line_hardware', 'Special_Equipments', 'None'])->default('Line_hardware');
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_products_item');
    }
};

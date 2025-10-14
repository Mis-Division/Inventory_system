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

        Schema::create('tbl_purchase_order_items', function (Blueprint $table) {
            $table->id('po_item_id')->primary();
            $table->foreignId('po_id')
                ->onDelete('cascade');
            $table->foreignId('product_id')
                ->onDelete('cascade');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            // $table->decimal('unit_price', 10, 2);
            // $table->decimal('total_price', 10, 2);
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
        Schema::dropIfExists('tbl_purchase_order_items');
    }
};

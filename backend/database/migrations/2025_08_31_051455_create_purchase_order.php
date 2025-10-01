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
        Schema::create('tbl_purchase_order', function (Blueprint $table) {
            $table->id('po_id');
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')
                ->onDeete('cascade');
            $table->date('order_date');
            $table->date('delivery_date');
            $table->enum('status', ['Pending', 'Received', 'Cancelled'])->default('Pending');
            $table->enum('payment_status', ['Unpaid', 'Partially Paid', 'Paid'])->default('Unpaid');
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
        Schema::dropIfExists('tbl_purchase_order');
        // Schema::dropIfExists('purchase_order_items');
    }
};

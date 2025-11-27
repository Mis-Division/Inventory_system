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
        Schema::create('tbl_receive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('r_id')
                ->constrained('tbl_receive')
                ->onDelete('cascade');
            $table->foreignId('ItemCode_id')
                ->nullable()
                ->constrained('tbl_item_code')
                ->onDelete('set null');
            $table->integer('quantity_order')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->string('product_type')->nullable();

            // 🟩 new column
            $table->enum('status', ['Partial', 'Complete'])->default('Partial');

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_receive_items');
    }
};

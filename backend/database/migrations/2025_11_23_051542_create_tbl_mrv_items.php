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
Schema::create('tbl_mrv_items', function (Blueprint $table) {
    $table->engine = 'InnoDB';

    $table->id('mrv_item_id');

    $table->unsignedBigInteger('mrv_id'); // MUST MATCH tbl_mrv
    $table->unsignedBigInteger('itemcode_id');

    $table->integer('requested_qty');
    $table-string('product_type');
    $table->integer('approved_qty')->nullable();
    $table->integer('issued_qty')->nullable();

    $table->foreign('mrv_id')
          ->references('mrv_id')
          ->on('tbl_mrv')
          ->onDelete('cascade');

    $table->foreign('itemcode_id')
          ->references('ItemCode_id')
          ->on('tbl_item_code')
          ->onDelete('cascade');

    $table->timestamps();
    $table->timestamps('deleted_at');
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_mrv_items');
    }
};

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
      Schema::create('tbl_mcrt_items', function (Blueprint $table) {
    $table->engine = 'InnoDB';
    $table->id('mcrt_item_id');

    $table->unsignedBigInteger('mcrt_id');
    $table->unsignedBigInteger('itemcode_id');

    $table->integer('returned_qty');
    $table->decimal('cost', 12, 2);

    $table->enum('condition', ['Good as new', 'Damaged', 'For Repair'])
          ->default('Good as new');

    $table->timestamps();
        $table->softDeletes();     // deleted_at

    // Foreign keys
    $table->foreign('mcrt_id')
        ->references('mcrt_id')
        ->on('tbl_mcrt')
        ->onDelete('cascade');

    // Optional FK (add only if you have table)
    // $table->foreign('itemcode_id')
    //     ->references('itemcode_id')
    //     ->on('tbl_itemcode')
    //     ->onDelete('restrict');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_mcrt_items');
    }
};

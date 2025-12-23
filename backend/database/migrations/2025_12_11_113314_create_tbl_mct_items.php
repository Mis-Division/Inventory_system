<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('tbl_mct_items', function (Blueprint $table) {
        $table->bigIncrements('mct_item_id');

        // Link to MCT Header
        $table->unsignedBigInteger('mct_id');
        $table->unsignedBigInteger('itemcode_id');

        // Item details
        $table->decimal('requested_qty', 12, 2);
        $table->decimal('unit_cost', 12, 2);
        $table->decimal('total_amount', 12, 2);

        $table->string('remarks', 255)->nullable();
        $table->enum('status',['RELEASED','CANCELLED','REVERSED'])->default('RELEASED');

        // Timestamps
        $table->timestamps();
        $table->softDeletes();

        // Optional FK
        // $table->foreign('mct_id')->references('mct_id')->on('tbl_mct');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_mct_items');
    }
};

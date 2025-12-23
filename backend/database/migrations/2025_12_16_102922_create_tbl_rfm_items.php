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
Schema::create('tbl_rfm_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('rfm_id')
        ->constrained('tbl_rfm')
        ->cascadeOnDelete();

    // LINK TO MASTER ITEM
    $table->unsignedBigInteger('itemcode_id');

    // PAPER-FORM VALUES
    $table->string('material_description');
    $table->string('requested_qty');
    $table->enum('status',['PENDING','APPROVED','REJECTED']);
    $table->text('remarks')->nullable();

    $table->timestamps();
     $table->softDeletes(); 
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_rfm_items');
    }
};

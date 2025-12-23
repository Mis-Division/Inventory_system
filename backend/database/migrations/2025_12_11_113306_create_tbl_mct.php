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
    Schema::create('tbl_mct', function (Blueprint $table) {
        $table->bigIncrements('mct_id');
        $table->string('mct_number', 50)->unique();

        // MRV Reference
        $table->unsignedBigInteger('mrv_id'); 
        $table->string('mrv_number', 50);

        // Module Type
        $table->string('module', 100);

        // People Involved
        $table->unsignedBigInteger('requested_by');
        $table->unsignedBigInteger('issued_by')->nullable();
        $table->unsignedBigInteger('received_by')->nullable();

        // Remarks & Status
        $table->text('remarks')->nullable();
        $table->enum('status', ['PENDING', 'COMPLETED', 'CANCELLED'])->default('PENDING');

        // Total
        $table->decimal('grand_total', 12, 2)->default(0);

        // Timestamps
        $table->timestamps();
        $table->softDeletes();

        // Foreign key (optional but recommended)
        // $table->foreign('mrv_id')->references('mrv_id')->on('tbl_mrv');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_mct');
    }
};

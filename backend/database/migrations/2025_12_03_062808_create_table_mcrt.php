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
    Schema::create('tbl_mcrt', function (Blueprint $table) {
    $table->engine = 'InnoDB';
    $table->id('mcrt_id');

    $table->string('mcrt_number')->unique();
    $table->string('returned_by');
    $table->string('work_order')->nullable();
    $table->string('job_order')->nullable();
    $table->string('received_by');
    $table->decimal('grand_total', 12, 2);

    $table->timestamps();
    $table->softDeletes();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_mcrt');
    }
};

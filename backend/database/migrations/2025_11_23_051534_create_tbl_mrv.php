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
Schema::create('tbl_mrv', function (Blueprint $table) {
    $table->engine = 'InnoDB';

    $table->id('mrv_id'); // BIGINT UNSIGNED (PRIMARY KEY)
    $table->string('mrv_number');
    $table->string('requested_by');
    $table->string('department');
    $table->string('approved_by')->nullable();
    $table->string('created_by');
    $table->enum('status', ['Pending', 'Approved'])->default('Pending');
    $table->timestamps('deleted_at');
    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_mrv');
    }
};

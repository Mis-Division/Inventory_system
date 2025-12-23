<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_mrv', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id('mrv_id');

            // MRV INFO
            $table->string('mrv_number')->unique();
            $table->date('mrv_date')->nullable();

            // RFM REFERENCE
            $table->unsignedBigInteger('rfm_id');
            $table->string('rfm_number');

            // REQUEST INFO
            $table->string('requested_by');
            $table->string('department');
            $table->string('approved_by')->nullable();
            $table->string('created_by');

            // STATUS
            $table->enum('status', ['Pending', 'Approved'])->default('Pending');

            // SOFT DELETE + TIMESTAMPS
            $table->softDeletes();
            $table->timestamps();

            // FOREIGN KEY (OPTIONAL BUT RECOMMENDED)
            $table->foreign('rfm_id')
                  ->references('rfm_id')
                  ->on('tbl_rfm')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mrv');
    }
};

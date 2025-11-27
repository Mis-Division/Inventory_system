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
    Schema::create('tbl_rr_history', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('r_id'); // reference sa tbl_receive
        $table->unsignedBigInteger('user_id'); // sino nag-update
        $table->json('old_data'); // dati bago i-update
        $table->json('new_data'); // bagong data
        $table->string('action'); // 'update', 'create', etc.
        $table->timestamps();

        $table->foreign('r_id')->references('r_id')->on('tbl_receive')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rr_history');
    }
};

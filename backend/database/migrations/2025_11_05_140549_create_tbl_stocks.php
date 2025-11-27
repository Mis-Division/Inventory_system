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
        Schema::create('tbl_stocks', function (Blueprint $table) {
             $table->id();
            $table->foreignId('ItemCode_id')
                ->constrained('tbl_item_code')
                ->onDelete('cascade');
            $table->integer('quantity_onhand')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_stocks');
    }
};

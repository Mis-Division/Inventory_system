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
        Schema::create('tbl_transactions', function (Blueprint $table) {
    $table->id('transaction_id');
    $table->foreignId('ItemCode_id')
          ->nullable()
          ->constrained('tbl_item_code') // foreign table name
          ->onDelete('cascade');          // optional: what happens if the item is deleted
    $table->enum('movement_type', ['IN','OUT'])->default('IN');
    $table->integer('quantity')->default(0);
    $table->string('reference')->nullable();
    $table->string('user_id')->nullable();
    $table->string('created_by');
    $table->string('updated_by')->nullable();
    $table->timestamps();

    $table->unique(['ItemCode_id', 'movement_type', 'reference'], 'unique_transaction');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_transactions');
    }
};

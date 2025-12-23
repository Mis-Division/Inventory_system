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
       Schema::create('tbl_account_codes', function (Blueprint $table) {
    $table->id();
    $table->string('account_code')->unique();     // e.g. 160-001
    $table->string('description')->nullable();    // e.g. Concrete Pole
    $table->decimal('percent_value', 5, 2)->default(0); // e.g. 6.00
     $table->tinyInteger('account_type')->default(1); 
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_account_codes');
    }
};

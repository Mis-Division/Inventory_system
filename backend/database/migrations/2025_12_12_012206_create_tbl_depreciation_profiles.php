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
  Schema::create('tbl_depreciation_profiles', function (Blueprint $table) {
    $table->id();

    // RELATION LINKS
    $table->bigInteger('itemcode_id')->nullable();
    $table->bigInteger('mct_item_id')->nullable();

    // ACCOUNT CODE & PERCENT
    $table->string('account_code')->nullable();
    $table->decimal('account_percent_value', 5, 2)->default(0);

    // LIFESPAN (100 / percent)
    $table->decimal('lifespan_years', 10, 2)->default(0);

    // COST VALUES
    $table->decimal('original_cost', 12, 2)->default(0);
    $table->decimal('book_value', 12, 2)->default(0);

    // YEARS USED / REMAINING LIFE
    $table->decimal('years_used_before_return', 10, 2)->default(0);
    $table->decimal('remaining_life_years', 10, 2)->default(0);

    // DEPRECIATION RATE & SCHEDULE
    $table->decimal('depreciation_rate', 5, 2)->default(10.00);
    $table->decimal('monthly_depreciation', 12, 2)->default(0);

    // IMPORTANT DATES
    $table->date('acquisition_date')->nullable();           // first release (MCT)
    $table->date('depreciation_start_date')->nullable();    // first return (MCRT)

    // ACCUMULATION
    $table->integer('accumulated_months')->default(0);
    $table->decimal('accumulated_depreciation', 12, 2)->default(0);

    // STATUS
    $table->enum('status', ['PENDING', 'ACTIVE', 'FINISHED'])->default('PENDING');

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_depreciation_profiles');
    }
};

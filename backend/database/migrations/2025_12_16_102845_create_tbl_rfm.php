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
                Schema::create('tbl_rfm', function (Blueprint $table) {
            $table->id('rfm_id');

            // Document
            $table->string('rfm_number', 20)->unique();
            $table->date('rfm_date');

            // Work info
            $table->text('work_description');
            $table->string('location');

            // Classification
            $table->string('work_type');

            // Scope (STRING AS REQUESTED)
            $table->string('primary_lines_retired');
            $table->string('secondary_lines_retired');
            $table->string('busted_transformer');
            $table->string('service_drop_wire');
            $table->string('cut_of_assembly');
            $table->string('meters');                 // ✅ separated
            $table->string('poles');     


            // MCO
            $table->text('mco_details');

            // Remarks
            $table->text('remarks');

            // Requesting
            $table->string('requested_by');
            $table->string('department');
            $table->string('area_engineering');
 

            // Warehouse
            $table->string('warehouse_initial');
            $table->date('warehouse_date');

            $table->string('created_by');
            $table->timestamps();
              $table->softDeletes(); 
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_rfm');
    }
};

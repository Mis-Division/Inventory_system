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
                    Schema::create('tbl_receive', function (Blueprint $table) {
            $table->id('r_id');
            $table->string('po_number')->nullable();
            $table->string('invoice_number')->nullable();
            $table->foreignId('supplier_id')->nullable()
                ->constrained('tbl_suppliers')
                ->onDelete('set null');
            $table->string('dr_number')->nullable();
            $table->string('rr_number')->nullable();
            $table->string('received_by')->nullable();
            $table->date('receive_date')->nullable();
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_receive');
    }
};

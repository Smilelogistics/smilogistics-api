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
        Schema::create('shipment_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->string('charge_type')->nullable();
            $table->string('comment')->nullable()->comment('WILL APPEAR ON THE INVOICE');
            $table->string('units')->nullable();
            $table->string('rate')->nullable();
            $table->string('amount')->nullable();
            $table->string('discount')->nullable();
            $table->string('internal_notes')->nullable();
            $table->boolean('billed')->default(false)->comment('IF THE CHARGE HAS BEEN BILLED');
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('total')->nullable();
            $table->string('net_total')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_charges');
    }
};

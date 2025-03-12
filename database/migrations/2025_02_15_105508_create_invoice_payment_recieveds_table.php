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
        Schema::create('invoice_payment_recieveds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('check_number')->nullable()->comment('CHECK NUMBER IF PAID VIA CHECK OR REFERENCE NUMBER IF PAID VIA CASH OR ONLINE PAYMENT');
            $table->string('amount')->nullable();
            $table->string('processing_fee_percent')->nullable();
            $table->string('processing_fee_flate_rate')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_recieveds');
    }
};

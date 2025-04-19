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
            $table->string('payment_date', 30)->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('check_number', 50)->nullable()->comment('CHECK NUMBER IF PAID VIA CHECK OR REFERENCE NUMBER IF PAID VIA CASH OR ONLINE PAYMENT');
            $table->decimal('amount', 20, 2)->nullable();
            $table->string('processing_fee_percent', 10)->nullable();
            $table->string('processing_fee_flate_rate', 20)->nullable();
            $table->text('notes')->nullable();
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

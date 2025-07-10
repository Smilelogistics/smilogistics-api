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
        Schema::create('invoice_payment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('payment_date', 30)->nullable();
            $table->string('paid_via', 30)->nullable();
            $table->string('check_number', 50)->nullable()->comment('Check number if paid via check, or reference number if paid via cash or online payment');
            $table->decimal('payment_amount', 20, 2)->nullable();
            $table->decimal('processing_fee_per', 5, 2)->nullable();
            $table->decimal('processing_fee_flat', 20, 2)->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_repayment_amount', 20, 2)->nullable()->after('net_total');
            $table->decimal('remaining_balance', 20, 2)->nullable()->after('total_repayment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['total_repayment_amount', 'remaining_balance']);
        });

        Schema::dropIfExists('invoice_payment_records');
    }
};

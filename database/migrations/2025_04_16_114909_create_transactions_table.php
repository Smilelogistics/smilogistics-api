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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('payment_method'); // paystack, stripe, paypal, flutterwave
            $table->string('payment_gateway_ref')->unique();
            $table->string('status')->default('pending'); // pending, success, failed, refunded
            $table->string('description')->nullable();
            $table->string('channel')->nullable();
            $table->string('payment_type')->nullable(); // one-time, recurring
            $table->json('meta')->nullable(); // store raw response
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('customer_email')->nullable();
            $table->string('auth_token')->nullable();
            $table->timestamps();
        });

        Schema::table('shipment_charges', function (Blueprint $table) {
            $table->string('total_discount')->after('discount')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('total_discount')->after('credit_memo')->nullable();
            $table->string('net_total')->after('credit_memo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');

        Schema::table('shipment_charges', function (Blueprint $table) {
            $table->dropColumn('total_discount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('total_discount');
            $table->dropColumn('net_total');
        });
    }
};

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
            $table->string('payment_method', 20); // paystack, stripe, paypal, flutterwave
            $table->string('payment_gateway_ref')->unique();
            $table->string('status')->default('pending'); // pending, success, failed, refunded
            $table->text('description')->nullable();
            $table->string('channel', 20)->nullable();
            $table->string('payment_type', 20)->nullable(); // one-time, recurring
            $table->json('meta')->nullable(); // store raw response
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device', 100)->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('customer_email', 80)->nullable();
            $table->string('auth_token')->nullable();
            $table->timestamps();
        });

        Schema::table('shipment_charges', function (Blueprint $table) {
            $table->string('total_discount')->after('discount')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('total_discount')->nullable();
            $table->decimal('net_total', 20, 2)->nullable();
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

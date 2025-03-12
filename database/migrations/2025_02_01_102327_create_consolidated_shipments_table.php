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
        Schema::create('consolidated_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_fname')->nullable();
            $table->string('customer_lname')->nullable();
            $table->string('customer_mname')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_country')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_zip')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('recipient_fname')->nullable();
            $table->string('recipient_lname')->nullable();
            $table->string('recipient_mname')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_address')->nullable();
            $table->string('recipient_country')->nullable();
            $table->string('recipient_state')->nullable();
            $table->string('recipient_zip')->nullable();
            $table->string('recipient_city')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('type_of_packaging')->nullable();
            $table->string('courier_company')->nullable();
            $table->string('service_mode')->nullable();
            $table->string('delivery_time')->nullable();
            $table->string('delivery_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('images')->nullable();
            $table->string('tracking_number')->unique(); // Master tracking number
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('total_weight', 8, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consolidated_shipments');
    }
};

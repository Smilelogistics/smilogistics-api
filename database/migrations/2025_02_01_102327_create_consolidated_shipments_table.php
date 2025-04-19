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
            $table->string('customer_fname', 30)->nullable();
            $table->string('customer_lname', 30)->nullable();
            $table->string('customer_mname', 30)->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_country', 20)->nullable();
            $table->string('customer_state', 20)->nullable();
            $table->string('customer_zip', 20)->nullable();
            $table->string('customer_city', 20)->nullable();
            $table->string('customer_email', 80)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('recipient_fname', 30)->nullable();
            $table->string('recipient_lname', 30)->nullable();
            $table->string('recipient_mname', 30)->nullable();
            $table->string('recipient_email', 80)->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->string('recipient_address')->nullable();
            $table->string('recipient_country', 30)->nullable();
            $table->string('recipient_state', 30)->nullable();
            $table->string('recipient_zip', 20)->nullable();
            $table->string('recipient_city', 20)->nullable();
            $table->string('shipping_method', 15)->nullable();
            $table->string('type_of_packaging', 30)->nullable();
            $table->string('courier_company', 150)->nullable();
            $table->string('service_mode', 30)->nullable();
            $table->string('delivery_time', 80)->nullable();
            $table->string('delivery_date', 80)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('delivery_status', 80)->nullable();
            $table->string('images')->nullable();
            $table->string('tracking_number', 100)->unique(); // Master tracking number
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('status', 15)->default('pending');
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

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
        Schema::create('consolidate_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('consolidate_tracking_number')->nullable()->unique();
            $table->enum('consolidation_type', ['Personal', 'Commercial', 'Bulk Order'])->default('Personal');

            // Customer & Receiver Info
            $table->string('consolidated_for')->nullable(); // Customer Name
            $table->string('customer_email')->nullable(); // /Email
            $table->string('customer_phone')->nullable(); // Phone
            $table->string('receiver_name')->nullable();
            $table->text('receiver_address')->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('receiver_phone')->nullable();

            // Logistics & Routing
            $table->string('origin_warehouse')->nullable();
            $table->string('destination_warehouse')->nullable();
            $table->date('expected_departure_date')->nullable();
            $table->date('expected_arrival_date')->nullable();

            // Cost & Payment
            $table->decimal('total_weight', 10, 2)->default(0);
            $table->decimal('total_shipping_cost', 12, 2)->default(0);
            $table->decimal('handling_fee', 12, 2)->default(0);
            $table->enum('payment_status', ['Paid', 'Pending', 'Partially Paid'])->default('Pending');
            $table->enum('payment_method', ['Cash', 'Card', 'Wallet', 'Transfer', 'Other'])->nullable();
            $table->enum('accepted_status', ['Accepted', 'Rejected', 'Pending'])->default('Pending');
            $table->string('status', 100)->default('Pending');
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });


        Schema::create('consolidate_shipment_docs', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->nullable();
            $table->string('public_id')->nullable();
            $table->foreignId('consolidate_shipment_id')->constrained()->onDelete('cascade');
             $table->string('invoice_path')->nullable(); // File path for invoice/manifest
             $table->string('proof_of_delivery_path')->nullable(); // Signature/image path
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('consolidate_shipments');
        Schema::enableForeignKeyConstraints();
        Schema::dropIfExists('consolidate_shipment_docs');
    }
};

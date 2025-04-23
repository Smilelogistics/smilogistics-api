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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete()->comment('Assigned driver');
            $table->string('delivery_tracking_number', 20)->nullable()->unique();
            $table->timestamp('assigned_at')->nullable();
            $table->enum('driver_status', ['pending', 'accepted', 'in_transit', 'delivered', 'cancelled'])->default('pending');

            $table->decimal('origin_lat', 10, 8)->nullable();
            $table->decimal('origin_lng', 11, 8)->nullable();
            $table->decimal('destination_lat', 10, 8)->nullable();
            $table->decimal('destination_lng', 11, 8)->nullable();

            
            $table->string('receiver_name', 80)->nullable();
            $table->text('receiver_address')->nullable();
            $table->string('receiver_email', 80)->nullable();
            $table->string('receiver_phone', 20)->nullable();
             // Distance and Routing
             $table->decimal('distance_km', 8, 2)->nullable();
             $table->integer('estimated_minutes')->nullable();
             $table->text('route_polyline')->nullable()->comment('For map rendering');
              // Service Information
            $table->string('service_type')->default('standard')->comment('standard, express, same-day');
            $table->decimal('base_rate', 8, 2)->nullable();
            $table->decimal('weight_surcharge', 8, 2)->default(0);
            $table->decimal('service_fee', 8, 2)->default(0);
            
            // Status Tracking
            $table->string('current_status')->default('created')->comment('created, processing, in_transit, delivered');
            $table->text('status_notes')->nullable();

              // Financial Information
              $table->decimal('total_shipping_cost', 10, 2);
              $table->decimal('amount_paid', 10, 2)->default(0);
              $table->string('payment_method')->nullable()->comment('cash, card, transfer, etc.');
              $table->string('payment_status')->default('pending')->comment('pending, partial, paid');

              // Indexes
              $table->index('delivery_tracking_number');
              $table->index('driver_status');
              $table->index('payment_status');
            
            $table->timestamps();
        });


        // Create table for status history
        Schema::create('delivery_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at')->useCurrent();
            $table->json('metadata')->nullable();
        });
        
        Schema::table('shipment_uploads', function (Blueprint $table) {
            $table->string('public_id')->after('id')->nullable();
        });
        
        Schema::table('carrier_docs', function (Blueprint $table) {
            $table->string('public_id')->after('id')->nullable();
        });
        
        Schema::table('driver_docs', function (Blueprint $table) {
            $table->string('public_id')->after('id')->nullable();
        });
        
        // Schema::table('consolidate_shipment_docs', function (Blueprint $table) {
        //     $table->string('public_id')->after('id')->nullable();
        // });
        
        Schema::table('truck_docs', function (Blueprint $table) {
            $table->string('public_id')->after('id')->nullable();
        });
        
        Schema::table('invoice_docs', function (Blueprint $table) {
            $table->string('public_id')->after('id')->nullable();
        });
        
        Schema::table('bike_docs', function (Blueprint $table) {
            $table->string('public_id')->after('id')->nullable();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->text('description');
            $table->string('billing_cycle')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->nullable();
            $table->string('currency')->default('USD');
            $table->integer('sort_order')->nullable();
            $table->json('features')->nullable();
            $table->integer('max_users')->nullable();
            $table->integer('storage_limit')->nullable();
            $table->string('plan_code')->unique()->nullable();
            $table->decimal('setup_fee', 10, 2)->nullable();
            $table->string('support_level')->nullable();
            $table->integer('shipment_count')->nullable();
            $table->integer('truck_count')->nullable();
            $table->integer('driver_count')->nullable();
            $table->integer('customer_count')->nullable();
            $table->timestamps();
        });
        
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_status_history');

        Schema::table('shipment_uploads', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('carrier_docs', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('driver_docs', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('consolidate_shipment_docs', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('truck_docs', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('invoice_docs', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
        Schema::table('bike_docs', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });

        
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('plans');
    }
};

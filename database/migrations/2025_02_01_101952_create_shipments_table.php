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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('signature', 100)->nullable();
            $table->string('office', 20)->nullable();
            $table->string('load_type', 20)->nullable();
            $table->text('load_type_note')->nullable();
            $table->string('brokered', 80)->nullable();
            $table->string('shipment_prefix', 10)->nullable();
            $table->string('shipment_image', 90)->nullable();
            $table->string('reference_number', 30)->nullable();
            $table->string('bill_of_laden_number', 30)->nullable();
            $table->string('booking_number', 30)->nullable();
            $table->string('po_number', 30)->nullable();
            $table->string('shipment_weight', 30)->nullable();
            $table->string('commodity', 30)->nullable();
            $table->string('pieces', 30)->nullable();
            $table->string('pickup_number', 30)->nullable();
            $table->string('overweight_hazmat', 50)->nullable();
            $table->string('tags')->nullable();
            $table->string('genset_number', 50)->nullable();
            $table->string('reefer_temp', 100)->nullable();
            $table->string('seal_number', 30)->nullable();


            $table->decimal('total_miles', 10, 2)->default(0.00);
            $table->decimal('loaded_miles', 10, 2)->default(0.00);
            $table->decimal('empty_miles', 10, 2)->default(0.00);
            $table->decimal('dh_miles', 10, 2)->default(0.00);
            $table->decimal('fuel_rate_per_gallon', 8, 2)->default(0.00);
            $table->decimal('mpg', 8, 2)->default(0.00);
            $table->decimal('fuel_cost', 10, 2)->default(0.00);

            $table->decimal('total_fuel_cost', 10, 2)->nullable();
            $table->string('broker_name', 50)->nullable()->comment('FRIEGHT BROKER INFORMATION');
            $table->string('broker_email', 30)->nullable()->comment('FRIEGHT BROKER EMAIL');
            $table->string('broker_phone', 20)->nullable();
            $table->string('broker_reference_number', 30)->nullable();
            $table->string('broker_batch_number', 30)->nullable();
            $table->string('broker_seq_number', 30)->nullable();
            $table->string('broker_sales_rep', 50)->nullable();
            $table->string('broker_edi_api_shipment_number', 50)->nullable();
            $table->text('broker_notes')->nullable();



            // $table->string('agency')->nullable();
            // $table->string('origin')->nullable();
            // $table->string('customer_f_name')->nullable();
            // $table->string('customer_l_name')->nullable();
            // $table->string('customer_m_name')->nullable();
            // $table->string('customer_country')->nullable();
            // $table->string('customer_state')->nullable();
            // $table->string('customer_zip')->nullable();
            // $table->string('customer_city')->nullable();
            // $table->string('customer_address')->nullable();
            // $table->string('customer_phone')->nullable();
            // $table->string('customer_email')->nullable();
            // $table->string('shipment_tracking_number')->nullable();
            // $table->string('shipment_mode')->nullable();
            // $table->string('shipment_packaging_type')->nullable();
            // $table->string('courier_company')->nullable();
            // $table->string('service_mode')->nullable();
            // $table->string('delivery_time')->nullable();
            // $table->string('store_supplier')->nullable();
            // $table->string('purchase_price')->nullable();
            // $table->string('amount')->nullable();
            // $table->string('shipment_description')->nullable();
            // $table->string('shipment_declared_value')->nullable();
            // $table->string('shipment_weight')->nullable();
            // $table->string('shipment_length')->nullable();
            // $table->string('shipment_width')->nullable();
            // $table->string('shipment_height')->nullable();
            // $table->string('weight_volume')->nullable();
            // $table->string('price_per_kg')->nullable();
            // $table->string('discount')->nullable();
            // $table->string('valued_assured')->nullable();
            // $table->string('shipment_insurance')->nullable();
            // $table->string('custom_duties')->nullable();
            // $table->string('tax')->nullable();
            // $table->string('rate_taxes_declared_value')->nullable();
            // $table->string('reissue_reason')->nullable();
            // $table->string('fixed_charge')->nullable();
            // $table->string('total_amount')->nullable();
            // $table->string('shipment_status')->default('pending')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

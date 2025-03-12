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
            $table->string('signature')->nullable();
            $table->string('office')->nullable();
            $table->string('load_type')->nullable();
            $table->string('load_type_note')->nullable();
            $table->string('brokered')->nullable();
            $table->string('shipment_prefix')->nullable();
            $table->string('shipment_image')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('bill_of_laden_number')->nullable();
            $table->string('booking_number')->nullable();
            $table->string('po_number')->nullable();
            $table->string('shipment_weight')->nullable();
            $table->string('commodity')->nullable();
            $table->string('pieces')->nullable();
            $table->string('pickup_number')->nullable();
            $table->string('overweight_hazmat')->nullable();
            $table->string('tags')->nullable();
            $table->string('genset_number')->nullable();
            $table->string('reefer_temp')->nullable();
            $table->string('seal_number')->nullable();
            $table->string('total_miles')->nullable();
            $table->string('loaded_miles')->nullable();
            $table->string('empty_miles')->nullable();
            $table->string('dh_miles')->nullable();
            $table->string('fuel_rate_per_gallon')->nullable();
            $table->string('mpg')->nullable();
            $table->string('total_fuel_cost')->nullable();
            $table->string('broker_name')->nullable()->comment('FRIEGHT BROKER INFORMATION');
            $table->string('broker_email')->nullable()->comment('FRIEGHT BROKER EMAIL');
            $table->string('broker_phone')->nullable();
            $table->string('broker_reference_number')->nullable();
            $table->string('broker_batch_number')->nullable();
            $table->string('broker_seq_number')->nullable();
            $table->string('broker_sales_rep')->nullable();
            $table->string('broker_edi_api_shipment_number')->nullable();
            $table->string('broker_notes')->nullable();



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

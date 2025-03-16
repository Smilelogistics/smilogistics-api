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
        Schema::table('shipments', function (Blueprint $table) {
            
            $table->string('shipment_type')->nullable()->after('shipment_tracking_number');
            $table->string('shipper_name')->nullable()->after('shipment_tracking_number');
            $table->text('ocean_shipper_address')->nullable()->after('shipment_tracking_number');
            $table->string('ocean_shipper_reference_number')->nullable()->after('shipment_tracking_number');
            $table->string('carrier_name')->nullable()->after('shipment_tracking_number');
            $table->string('carrier_reference_number')->nullable()->after('shipment_tracking_number');
            $table->string('ocean_bill_of_ladening_number')->nullable()->after('shipment_tracking_number');
            $table->string('consignee')->nullable()->after('shipment_tracking_number');
            $table->string('consignee_phone')->nullable()->after('shipment_tracking_number');
            $table->string('consignee_email')->nullable()->after('shipment_tracking_number');
            $table->string('first_notify_party_name')->nullable()->after('shipment_tracking_number');
            $table->string('first_notify_party_phone')->nullable()->after('shipment_tracking_number');
            $table->string('first_notify_party_email')->nullable()->after('shipment_tracking_number');
            $table->string('second_notify_party_name')->nullable()->after('shipment_tracking_number');
            $table->string('second_notify_party_phone')->nullable()->after('shipment_tracking_number');
            $table->string('second_notify_party_email')->nullable()->after('shipment_tracking_number');
            $table->string('pre_carrier')->nullable()->after('shipment_tracking_number');
            $table->string('vessel_aircraft_name')->nullable()->after('shipment_tracking_number');
            $table->string('voyage_number')->nullable()->after('shipment_tracking_number');
            $table->string('port_of_discharge')->nullable()->after('shipment_tracking_number');
            $table->string('place_of_delivery')->nullable()->after('shipment_tracking_number');
            $table->string('final_destination')->nullable()->after('shipment_tracking_number');
            $table->string('port_of_landing')->nullable()->after('shipment_tracking_number');
            $table->text('ocean_note')->nullable()->after('shipment_tracking_number');
            $table->decimal('ocean_freight_charges', 10, 2)->nullable()->after('shipment_tracking_number');
            $table->text('ocean_total_containers_in_words')->nullable()->after('shipment_tracking_number');
            $table->string('no_original_bill_of_landing')->nullable()->after('shipment_tracking_number');
            $table->string('original_bill_of_landing_payable_at')->nullable()->after('shipment_tracking_number');
            $table->date('shipped_on_board_date')->nullable()->after('shipment_tracking_number');
            $table->string('ocean_consignment_total')->nullable()->after('shipment_tracking_number');
            $table->string('ocean_total')->nullable()->after('shipment_tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('shipment_type');
            $table->dropColumn('shipper_name');
            $table->dropColumn('ocean_shipper_address');
            $table->dropColumn('ocean_shipper_reference_number');
            $table->dropColumn('carrier_name');
            $table->dropColumn('carrier_reference_number');
            $table->dropColumn('ocean_bill_of_ladening_number');
            $table->dropColumn('consignee');
            $table->dropColumn('consignee_phone');
            $table->dropColumn('consignee_email');
            $table->dropColumn('first_notify_party_name');
            $table->dropColumn('first_notify_party_phone');
            $table->dropColumn('first_notify_party_email');
            $table->dropColumn('second_notify_party_name');
            $table->dropColumn('second_notify_party_phone');
            $table->dropColumn('second_notify_party_email');
            $table->dropColumn('pre_carrier');
            $table->dropColumn('vessel_aircraft_name');
            $table->dropColumn('voyage_number');
            $table->dropColumn('port_of_discharge');
            $table->dropColumn('place_of_delivery');
            $table->dropColumn('final_destination');
            $table->dropColumn('port_of_landing');
            $table->dropColumn('ocean_note');
            $table->dropColumn('ocean_freight_charges');
            $table->dropColumn('ocean_total_containers_in_words');
            $table->dropColumn('no_original_bill_of_landing');
            $table->dropColumn('original_bill_of_landing_payable_at');
            $table->dropColumn('shipped_on_board_date');
            $table->dropColumn('ocean_consignment_total');
            $table->dropColumn('ocean_total');
        });
    }
};

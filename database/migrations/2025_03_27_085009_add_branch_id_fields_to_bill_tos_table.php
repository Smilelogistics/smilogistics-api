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
        Schema::table('bill_tos', function (Blueprint $table) {
            $table->foreignId('branch_id')->after('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->after('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->after('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->after('shipment_id')->nullable()->constrained()->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_tos', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['carrier_id']);
            $table->dropForeign(['driver_id']);
    
            $table->dropColumn(['branch_id', 'customer_id', 'carrier_id', 'driver_id']);
        });
    }
};

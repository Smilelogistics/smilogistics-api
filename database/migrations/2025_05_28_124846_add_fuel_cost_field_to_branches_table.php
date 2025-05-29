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
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('price_per_mile', 10, 2)->after('base_fee')->nullable();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('total_shipment_cost', 10, 2)->after('dispatcher_accepted_status')->nullable();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('price_per_mile');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('total_shipment_cost');
        });
    }
};

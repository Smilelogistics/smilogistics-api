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
            $table->foreignId('bike_id')->after('id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->after('id')->nullable()->constrained()
            ->nullOnDelete();
            $table->foreignId('truck_id')->after('id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['bike_id']);
            $table->dropForeign(['carrier_id']);
            $table->dropForeign(['truck_id']);

            $table->dropColumn(['bike_id', 'carrier_id', 'truck_id']);
        });
    }
};

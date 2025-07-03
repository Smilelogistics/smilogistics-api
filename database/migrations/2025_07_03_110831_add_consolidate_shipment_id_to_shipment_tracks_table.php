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
        if (!Schema::hasColumn('shipment_tracks', 'consolidate_shipment_id')) {
            Schema::table('shipment_tracks', function (Blueprint $table) {
                $table->foreignId('consolidate_shipment_id')
                      ->after('shipment_id')
                      ->nullable()
                      ->constrained()
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipment_tracks', 'consolidate_shipment_id')) {
            Schema::table('shipment_tracks', function (Blueprint $table) {
                $table->dropForeign(['consolidate_shipment_id']);
                $table->dropColumn('consolidate_shipment_id');
            });
        }
    }
};

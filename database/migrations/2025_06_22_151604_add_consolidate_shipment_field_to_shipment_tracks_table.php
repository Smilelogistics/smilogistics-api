<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            // Make shipment_id nullable
            $table->unsignedBigInteger('shipment_id')->nullable()->change();

            // Add consolidate_shipment_id as nullable foreign key
            $table->foreignId('consolidate_shipment_id')
                ->after('shipment_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
        });

        DB::statement('ALTER TABLE shipment_tracks ALTER COLUMN shipment_id SET NOT NULL');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('shipment_tracks', function (Blueprint $table) {
            // Make shipment_id NOT NULL again
            $table->unsignedBigInteger('shipment_id')->nullable(false)->change();

            // Drop the foreign key and column
            $table->dropForeign(['consolidate_shipment_id']);
            $table->dropColumn('consolidate_shipment_id');
        });
    }
};

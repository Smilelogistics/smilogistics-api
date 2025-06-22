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
        // First install doctrine/dbal if not already installed
        // composer require doctrine/dbal

        Schema::table('shipment_tracks', function (Blueprint $table) {
            // For PostgreSQL, we need to handle the column change differently
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE shipment_tracks ALTER COLUMN shipment_id DROP NOT NULL');
            } else {
                $table->unsignedBigInteger('shipment_id')->nullable()->change();
            }

            // Add consolidate_shipment_id as nullable foreign key
            $table->foreignId('consolidate_shipment_id')
                ->after('shipment_id')
                ->nullable()
                ->constrained('consolidate_shipments') // Make sure this matches your table name
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['consolidate_shipment_id']);
            $table->dropColumn('consolidate_shipment_id');

            // Handle PostgreSQL column change
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE shipment_tracks ALTER COLUMN shipment_id SET NOT NULL');
            } else {
                $table->unsignedBigInteger('shipment_id')->nullable(false)->change();
            }
        });
    }
};
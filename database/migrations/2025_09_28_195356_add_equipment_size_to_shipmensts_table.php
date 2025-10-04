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
            $table->string('booking_type', 50)->nullable()->after('voyage_number');
            $table->string('cargo_origin', 80)->nullable()->after('voyage_number');
            $table->string('equipment_type', 50)->nullable()->after('voyage_number');
            $table->integer('equipment_qty')->nullable()->after('voyage_number');
            $table->string('origin_ramp_rail', 100)->nullable()->after('voyage_number');
            //$table->string('port_of_landing', 100)->nullable()->after('voyage_number');
        });

        Schema::table('branches', function (Blueprint $table){
            $table->string('fax', 80)->nullable()->after('phone');
            $table->string('website')->nullable()->after('phone');
            $table->text('export_warning')->nullable()->after('phone');
        });

     
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'booking_type',
                'cargo_origin',
                'equipment_type',
                'equipment_qty',
                'origin_ramp_rail',
                //'port_of_landing',
            ]);
        });
        Schema::table('branches', function (Blueprint $table){
            $table->dropColumn([
                'fax',
                'website',
                'export_warning',
            ]);
        });
    }
};

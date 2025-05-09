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
            $table->foreignId('created_by_driver_id')
                  ->after('driver_id')
                  ->nullable()
                  ->constrained('drivers')
                  ->nullOnDelete();
        });
        
        Schema::table('consolidate_shipments', function (Blueprint $table) {
            $table->foreignId('created_by_driver_id')
                  ->after('driver_id')
                  ->nullable()
                  ->constrained('drivers')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['created_by_driver_id']);
            $table->dropColumn('created_by_driver_id');
        });
        
        Schema::table('consolidate_shipments', function (Blueprint $table) {
            $table->dropForeign(['created_by_driver_id']);
            $table->dropColumn('created_by_driver_id');
        });
    }
};

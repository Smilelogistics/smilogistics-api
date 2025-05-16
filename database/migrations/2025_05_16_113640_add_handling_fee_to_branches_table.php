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
            $table->decimal('handling_fee', 10, 2)->after('base_fee')->nullable();
        });

         Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('load_type_note_r', 10, 2)->after('brokered')->nullable();
        });

          Schema::table('consolidate_shipments', function (Blueprint $table) {
            $table->decimal('consolidate_net_total_charges', 10, 2)->after('status')->nullable();
            $table->decimal('consolidate_total_charges', 10, 2)->after('status')->nullable();
            $table->decimal('consolidate_total_discount_charges', 10, 2)->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('handling_fee');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('load_type_note_r');
        });

        Schema::table('consolidate_shipments', function (Blueprint $table) {
            $table->dropColumn('consolidate_net_total_charges');
            $table->dropColumn('consolidate_total_charges');
            $table->dropColumn('consolidate_total_discount_charges');
        });
    }
};

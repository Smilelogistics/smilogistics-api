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
            $table->integer('max_length')->nullable()->after('base_rate');
            $table->integer('max_height')->nullable()->after('base_rate');
        });

        Schema::table('consolidate_shipments', function (Blueprint $table) {
            $table->integer('total_length')->nullable()->after('handling_fee');
            $table->integer('total_height')->nullable()->after('handling_fee');
            $table->string('pickup_type')->nullable()->after('handling_fee');
            $table->text('description')->nullable()->after('handling_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consolidate_shipments', function (Blueprint $table) {
            $table->dropColumn('total_length');
            $table->dropColumn('total_height');
            $table->dropColumn('pickup_type');
            $table->dropColumn('description');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('max_length');
            $table->dropColumn('max_height');
        });
    }
};

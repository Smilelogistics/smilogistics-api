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
        Schema::table('carriers', function (Blueprint $table) {
            $table->json('state_served')->nullable()->change();
            $table->json('carrier_profile')->nullable()->change();
            $table->json('carries_this_cargo')->nullable()->change();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->string('state_served')->nullable()->change();
            $table->string('carrier_profile')->nullable()->change();
            $table->string('carries_this_cargo')->nullable()->change();
        });
    }
};

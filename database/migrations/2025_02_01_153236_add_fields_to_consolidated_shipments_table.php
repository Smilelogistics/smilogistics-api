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
        Schema::table('consolidated_shipments', function (Blueprint $table) {
            $table->foreignId('agency_id')->after('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->after('user_id')->nullable()->constrained()->nullOnDelete();   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consolidated_shipments', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropForeign(['branch_id']);
        });
    }
};

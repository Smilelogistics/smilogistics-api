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
            $table->string('carrier_profile')->after('name')->nullable();
            $table->string('status')->after('data_exchange_option')->nullable();
            $table->foreignId('customer_id')->after('branch_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->dropColumn('carrier_profile');
            $table->dropColumn('status');
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};

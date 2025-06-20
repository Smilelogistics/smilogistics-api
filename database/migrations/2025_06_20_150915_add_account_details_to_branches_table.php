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
            $table->string('trucking_bank_name')->after('subscription_type')->nullable();
            $table->string('trucking_account_name')->after('subscription_type')->nullable();
            $table->string('trucking_account_number')->after('subscription_type')->nullable();
            $table->string('trucking_routing')->after('subscription_type')->nullable();
            $table->string('trucking_zelle')->after('subscription_type')->nullable();
            $table->string('trucking_pay_cargo')->after('subscription_type')->nullable();
            $table->string('ocean_bank_name')->after('subscription_type')->nullable();
            $table->string('ocean_account_name')->after('subscription_type')->nullable();
            $table->string('ocean_account_number')->after('subscription_type')->nullable();
            $table->string('ocean_routing')->after('subscription_type')->nullable();
            $table->string('ocean_zelle')->after('subscription_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('trucking_bank_name');
            $table->dropColumn('trucking_account_name');
            $table->dropColumn('trucking_account_number');
            $table->dropColumn('trucking_routing');
            $table->dropColumn('trucking_zelle');
            $table->dropColumn('trucking_pay_cargo');
            $table->dropColumn('ocean_bank_name');
            $table->dropColumn('ocean_account_name');
            $table->dropColumn('ocean_account_number');
            $table->dropColumn('ocean_routing');
            $table->dropColumn('ocean_zelle');
        });
    }
};

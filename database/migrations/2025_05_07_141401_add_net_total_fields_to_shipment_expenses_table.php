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
        Schema::table('shipment_expenses', function (Blueprint $table) {
            $table->decimal('net_expense', 10, 2)->after('paid')->nullable();
            $table->decimal('expense_total', 10, 2)->after('paid')->nullable();
            $table->decimal('credit_total', 10, 2)->after('paid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_expenses', function (Blueprint $table) {
            $table->dropColumn('net_expense');
            $table->dropColumn('expense_total');
            $table->dropColumn('credit_total');
        });
    }
};

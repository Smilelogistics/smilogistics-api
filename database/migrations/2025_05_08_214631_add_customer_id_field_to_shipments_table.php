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
            $table->foreignId('customer_id')->after('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('net_expense', 10, 2)->after('delivery_type')->nullable();
            $table->decimal('expense_total', 10, 2)->after('delivery_type')->nullable();
            $table->decimal('credit_total', 10, 2)->after('delivery_type')->nullable();

            
            $table->decimal('total_charges', 10, 2)->after('delivery_type')->nullable();
            $table->decimal('total_discount_charges', 10, 2)->after('delivery_type')->nullable();
            $table->decimal('net_total_charges', 10, 2)->after('delivery_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
            $table->dropColumn('net_expense');
            $table->dropColumn('expense_total');
            $table->dropColumn('credit_total');
            $table->dropColumn('total_charges');
            $table->dropColumn('total_discount_charges');
            $table->dropColumn('net_total_charges');
        });
    }
};

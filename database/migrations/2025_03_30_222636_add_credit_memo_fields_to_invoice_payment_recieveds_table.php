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
        Schema::table('invoice_payment_recieveds', function (Blueprint $table) {
            $table->text('credit_memo')->after('notes')->nullable();
            $table->decimal('credit_amount', 20, 2)->after('notes')->nullable();
            $table->string('credit_date', 30)->after('notes')->nullable();
            $table->text('credit_note')->after('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_payment_recieveds', function (Blueprint $table) {
            $table->dropColumn('credit_memo');
            $table->dropColumn('credit_amount');
            $table->dropColumn('credit_date');
            $table->dropColumn('credit_note');
        });
    }
};

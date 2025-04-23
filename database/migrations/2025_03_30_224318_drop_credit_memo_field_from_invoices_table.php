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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['credit_memo', 'credit_amount', 'credit_date', 'credit_note']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('credit_memo')->nullable();
            $table->string('credit_amount')->nullable();
            $table->string('credit_date')->nullable();
            $table->text('credit_note')->nullable();
        });
    }
};

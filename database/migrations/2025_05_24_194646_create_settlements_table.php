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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bike_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('settlement_no')->nullable();
            $table->string('office', 80)->nullable();
            $table->date('settlement_date')->nullable();
            $table->string('settlement_type', 30)->nullable();
            $table->date('week_from')->nullable();
            $table->date('week_to')->nullable();
            $table->string('payee', 80)->nullable();
            $table->text('payee_note')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('check_payment_reference', 100)->nullable();
            $table->date('payment_date')->nullable();
            $table->text('payment_note')->nullable();
            $table->text('internal_notes')->nullable();

            $table->json('tags')->nullable();
            $table->decimal('total_payments', 10, 2)->nullable();
            $table->decimal('total_payments_discount', 10, 2)->nullable();
            $table->decimal('net_total_payments', 10, 2)->nullable();
            $table->decimal('total_escrow_release', 10, 2)->nullable();
            $table->decimal('total_deductions', 10, 2)->nullable();
            $table->timestamps();
        });

         Schema::create('settlement_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->onDelete('cascade');
            $table->string("file_path")->nullable();
            $table->string("public_id")->nullable();
            $table->string("file_name")->nullable();
            $table->timestamps();
        });

        Schema::create('settlement_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->onDelete('cascade');
            $table->string("deduction_type", 50)->nullable();
            $table->decimal('deduction_amount', 10, 2)->nullable();
            $table->text("deduction_comment")->nullable();
            $table->text("deduction_note")->nullable();
            $table->decimal('total_deductions', 10, 2)->nullable();
            $table->timestamps();
        });

         Schema::create('settlement_escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->onDelete('cascade');
            $table->string("escrow_release_account")->nullable();
            $table->decimal('escrow_release_amount', 10, 2)->nullable();
            $table->text("escrow_release_comment")->nullable();
            $table->text("escrow_release_note")->nullable();
            $table->decimal('total_escrow_release', 10, 2)->nullable();
            $table->timestamps();
        });

        
         Schema::create('settlement_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->onDelete('cascade');
            $table->string("payment_type", 80)->nullable();
            $table->text("comment")->nullable();
            $table->integer("units")->nullable();
            $table->integer("rate")->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('payment_total', 10, 2)->nullable();
            $table->decimal('payment_discount', 10, 2)->nullable();
            $table->decimal('net_total_payments', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_payments');
        Schema::dropIfExists('settlement_escrows');
        Schema::dropIfExists('settlement_deductions');
        Schema::dropIfExists('settlement_docs');
        Schema::dropIfExists('settlements');
        
    }
};

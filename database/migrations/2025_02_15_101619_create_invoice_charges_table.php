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
        Schema::create('invoice_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('load_number', 80)->nullable();
            $table->string('charge_type', 30)->nullable();
            $table->text('comment')->nullable()->comment('WILL APPEAR ON THE INVOICE');
            $table->integer('units')->nullable();
            $table->integer('unit_rate')->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->integer('discount')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('general_internal_notes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('isAccessorial')->default(false)->comment('IF THE CHARGE IS ACCESSORIAL');
            $table->decimal('total', 20, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_charges');
    }
};

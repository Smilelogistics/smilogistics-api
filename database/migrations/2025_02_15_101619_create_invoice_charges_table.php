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
            $table->string('load_number')->nullable();
            $table->string('charge_type')->nullable();
            $table->string('comment')->nullable()->comment('WILL APPEAR ON THE INVOICE');
            $table->string('units')->nullable();
            $table->string('unit_rate')->nullable();
            $table->string('amount')->nullable();
            $table->string('discount')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('general_internal_notes')->nullable();
            $table->string('tags')->nullable();
            $table->boolean('isAccessorial')->default(false)->comment('IF THE CHARGE IS ACCESSORIAL');
            $table->string('total')->nullable();
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

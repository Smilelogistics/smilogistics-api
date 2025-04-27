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
        Schema::create('consolidate_shipment_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('consolidate_shipment_id')->constrained()->onDelete('cascade');
            $table->string('charge_type', 50)->nullable();
            $table->text('comment')->nullable()->comment('WILL APPEAR ON THE INVOICE');
            $table->integer('units')->nullable();
            $table->integer('rate')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('discount', 20)->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('billed')->default(false)->comment('IF THE CHARGE HAS BEEN BILLED');
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('net_total', 10, 2)->nullable();
            $table->string('total_discount')->nullable();
            $table->string('status', 20)->nullable()->default('pending');
            $table->timestamps();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->integer('transport_type')->after('driver_type')->nullable()->comment('1 = Truck Driver, 2 = Bike Driver, 3 = Others, 4 = Temporary Driver');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consolidate_shipment_charges');

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('transport_type');
        });
    }
};

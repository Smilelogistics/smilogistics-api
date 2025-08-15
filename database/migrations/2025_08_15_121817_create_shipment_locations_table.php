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
        Schema::create('shipment_locations', function (Blueprint $table) {
            $table->id();
             $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['pickup', 'dropoff']);
            $table->integer('sequence')->default(1);
            $table->string('pick_up_type')->nullable(); // For pickup locations
            $table->string('drop_at_type')->nullable(); // For dropoff locations
            $table->string('location_name')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->date('appt_date')->nullable();
            $table->date('no_latter_than_date')->nullable();
            $table->time('no_latter_than_time')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'type', 'sequence']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_locations');
    }
};

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
        Schema::create('shipment_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('container', 150)->nullable();
            $table->string('container_size', 150)->nullable();
            $table->string('container_type', 150)->nullable();
            $table->string('container_number', 150)->nullable();
            $table->string('chasis', 150)->nullable();
            $table->string('isLoaded', 150)->nullable();
            $table->string('chasis_size', 150)->nullable();
            $table->string('chasis_type', 150)->nullable();
            $table->string('chasis_vendor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_containers');
    }
};

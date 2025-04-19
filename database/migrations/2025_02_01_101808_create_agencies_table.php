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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('agency_phone', 20)->nullable();
            $table->string('agency_address')->nullable();
            $table->string('agency_country', 20)->nullable();
            $table->string('agency_state', 20)->nullable();
            $table->string('agency_city', 20)->nullable();
            $table->string('agency_zip', 20)->nullable();
            $table->string('agency_status', 15)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};

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
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('license_internal_notes');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude'); 
            $table->decimal('speed', 8, 2)->nullable()->after('latitude');
            $table->decimal('heading', 8, 2)->nullable()->after('latitude');
            $table->decimal('accuracy', 8, 2)->nullable()->after('latitude');
            $table->enum('status', ['idle', 'on_delivery', 'offline'])->default('offline')->after('latitude');
            $table->timestamp('last_updated')->nullable()->after('longitude')->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'last_updated', 'speed', 'heading', 'accuracy', 'status']);
        });
    }
};

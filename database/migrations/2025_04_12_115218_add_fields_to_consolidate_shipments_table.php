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
        Schema::table('consolidate_shipments', function (Blueprint $table) {
            $table->string('consolidate_tracking_number', 20)->after('driver_id')->nullable()->unique();
            
            $table->string('customer_email', 80)->after('consolidated_for')->nullable(); // /Email
            $table->string('customer_phone', 20)->after('consolidated_for')->nullable(); // Phone
            $table->string('receiver_email', 80)->after('consolidated_for')->nullable();
            $table->string('receiver_phone', 20)->after('consolidated_for')->nullable();
            $table->text('internal_notes')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consolidate_shipments', function (Blueprint $table) {
            //
        });
    }
};

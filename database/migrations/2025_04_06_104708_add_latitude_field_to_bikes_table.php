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
        Schema::table('bikes', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->after('license_plate_number')->nullable(); // e.g., 40.712776
            $table->decimal('longitude', 10, 7)->after('license_plate_number')->nullable(); // e.g., -74.005974
            $table->boolean('available')->after('license_plate_number')->default(true);
            $table->string('status')->after('license_plate_number')->default('active')->nullable();
            $table->index('available');
            $table->index(['latitude', 'longitude']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->integer('dispatcher_accepted_status')->after('delivery_type')->nullable()->default(0)->comment('0 = Pending, 1 = Accepted, 2 = Rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bikes', function (Blueprint $table) {
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('available');
            $table->dropColumn('status');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('dispatcher_accepted_status');
        });
    }
};

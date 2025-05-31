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
    // First create a temporary column
    Schema::table('shipments', function (Blueprint $table) {
        $table->text('temps_shipment_status')->nullable()->after('shipment_status');
    });

    // Copy data to temporary column
    DB::table('shipments')->update([
        'temps_shipment_status' => DB::raw('shipment_status')
    ]);

    // Remove the original column
    Schema::table('shipments', function (Blueprint $table) {
        $table->dropColumn('shipment_status');
    });

    // Recreate the column with desired type - make it nullable first
    Schema::table('shipments', function (Blueprint $table) {
        $table->string('shipment_status', 80)->nullable()->after('temps_shipment_status');
    });

    // Copy data back - handle NULL values by providing a default
    DB::table('shipments')->update([
        'shipment_status' => DB::raw("COALESCE(temp_shipment_status, 'unknown')")
    ]);

    // Now make the column NOT NULL if needed
    Schema::table('shipments', function (Blueprint $table) {
        $table->string('shipment_status', 80)->nullable(false)->change();
    });

    // Remove temporary column
    Schema::table('shipments', function (Blueprint $table) {
        $table->dropColumn('temps_shipment_status');
    });

    // Add user columns
    Schema::table('users', function (Blueprint $table) {
        $table->string('otp')->after('email_verified_at')->nullable();
        $table->string('otp_status')->after('email_verified_at')->nullable();
        $table->timestamp('otp_expires_at')->after('email_verified_at')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('shipment_status', 80)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('otp');
            $table->dropColumn('otp_status');
            $table->dropColumn('otp_expires_at');
        });
    }
};

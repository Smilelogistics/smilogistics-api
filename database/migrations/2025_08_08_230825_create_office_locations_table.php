<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('price_per_gallon', 8, 2)->nullable()->after('mpg');
        });

        Schema::table('shipment_charges', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->index('branch_id');
        });

        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('short_name', 50)->nullable();
            $table->string('long_name', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 30)->nullable();
            $table->string('state', 30)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_locations');

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
        });

        Schema::table('shipment_charges', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('price_per_gallon');
        });
    }
};
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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->text('description');
            $table->string('billing_cycle')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->nullable();
            $table->string('currency')->default('USD');
            $table->integer('sort_order')->nullable();
            $table->json('features')->nullable();
            $table->integer('max_users')->nullable();
            $table->integer('storage_limit')->nullable();
            $table->string('plan_code')->unique()->nullable();
            $table->decimal('setup_fee', 10, 2)->nullable();
            $table->string('support_level')->nullable();
            $table->integer('shipment_count')->nullable();
            $table->integer('truck_count')->nullable();
            $table->integer('driver_count')->nullable();
            $table->integer('customer_count')->nullable();
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('plan_id')->after('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->after('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan_name', 100)->after('status')->nullable();
            $table->integer('duration')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['branch_id']);
        });
        
        // Then drop the columns
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('plan_id');
            $table->dropColumn('branch_id');
            $table->dropColumn('plan_name');
            $table->dropColumn('duration');
            $table->dropColumn('shipment_count');
            $table->dropColumn('truck_count');
            $table->dropColumn('driver_count');
            $table->dropColumn('customer_count');
        });

        Schema::dropIfExists('plans');

    }
};

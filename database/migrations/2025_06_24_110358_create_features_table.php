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
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->integer('status')->default(1)->nullable();
            $table->timestamps();
        });

        Schema::create('feature_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('feature_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('limits')->nullable(); // For feature limits if needed
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('status')->nullable()->default('active'); // active, canceled, expired, etc.
            $table->timestamps();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->integer('level')->nullable()->default(0)->after('id');
            $table->string('slug')->nullable()->after('name')->unique();
            $table->string('interval')->nullable()->after('price')->default('monthly'); // monthly, yearly, etc.
        });

        // Schema::table('shipment_tracks', function (Blueprint $table) {
        //     // For PostgreSQL, we need to handle the column change differently
        //     if (DB::getDriverName() === 'pgsql') {
        //         DB::statement('ALTER TABLE shipment_tracks ALTER COLUMN shipment_id DROP NOT NULL');
        //     } else {
        //         $table->unsignedBigInteger('shipment_id')->nullable()->change();
        //     }

        //     // Add consolidate_shipment_id as nullable foreign key
        //     $table->foreignId('consolidate_shipment_id')
        //         ->after('shipment_id')
        //         ->nullable()
        //         ->constrained('consolidate_shipments') // Make sure this matches your table name
        //         ->onDelete('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    { 
        // Schema::table('shipment_tracks', function (Blueprint $table) {
        //     // Drop the foreign key first
        //     $table->dropForeign(['consolidate_shipment_id']);
        //     $table->dropColumn('consolidate_shipment_id');

        //     // Handle PostgreSQL column change
        //     if (DB::getDriverName() === 'pgsql') {
        //         DB::statement('ALTER TABLE shipment_tracks ALTER COLUMN shipment_id SET NOT NULL');
        //     } else {
        //         $table->unsignedBigInteger('shipment_id')->nullable(false)->change();
        //     }
        // });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('level');
            $table->dropColumn('interval');
        });
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('feature_plan');
        Schema::dropIfExists('features');

       
    }
};

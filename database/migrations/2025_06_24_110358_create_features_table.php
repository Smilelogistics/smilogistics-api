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

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE consolidate_shipments ALTER COLUMN status TYPE VARCHAR(255)');
        } 
        // For MySQL/MariaDB, use the schema builder
        else {
            Schema::table('consolidate_shipments', function (Blueprint $table) {
                $table->string('status', 255)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    { 
        DB::table('consolidate_shipments')
            ->whereRaw('LENGTH(status) > 20')
            ->update(['status' => DB::raw('SUBSTRING(status, 1, 20)')]);

        // For PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE consolidate_shipments ALTER COLUMN status TYPE VARCHAR(20)');
        } 
        // For MySQL/MariaDB
        else {
            Schema::table('consolidate_shipments', function (Blueprint $table) {
                $table->string('status', 20)->change();
            });
        }

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

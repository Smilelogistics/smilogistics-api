<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::table('carriers', function (Blueprint $table) {
    //         $table->json('state_served')->nullable()->change();
    //         $table->json('carrier_profile')->nullable()->change();
    //         $table->json('carries_this_cargo')->nullable()->change();
    //     });
    // }
    // /**
    //  * Reverse the migrations.
    //  */
    // public function down(): void
    // {
    //     Schema::table('carriers', function (Blueprint $table) {
    //         $table->string('state_served')->nullable()->change();
    //         $table->string('carrier_profile')->nullable()->change();
    //         $table->string('carries_this_cargo')->nullable()->change();
    //     });
    // }

    public function up(): void
    {
        // First convert any existing string data to proper JSON format
        if (DB::connection()->getDriverName() === 'pgsql') {
            // For PostgreSQL, we need to ensure data is valid JSON before altering
            $this->convertPostgresDataToJson();
        }

        // Alter the columns to JSON type
        Schema::table('carriers', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                // PostgreSQL requires explicit casting
                DB::statement('ALTER TABLE carriers ALTER COLUMN state_served TYPE jsonb USING state_served::jsonb');
                DB::statement('ALTER TABLE carriers ALTER COLUMN carrier_profile TYPE jsonb USING carrier_profile::jsonb');
                DB::statement('ALTER TABLE carriers ALTER COLUMN carries_this_cargo TYPE jsonb USING carries_this_cargo::jsonb');
            } else {
                // MySQL/SQLite can handle this automatically
                $table->json('state_served')->nullable()->change();
                $table->json('carrier_profile')->nullable()->change();
                $table->json('carries_this_cargo')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                // Convert back to text for PostgreSQL
                DB::statement('ALTER TABLE carriers ALTER COLUMN state_served TYPE text USING state_served::text');
                DB::statement('ALTER TABLE carriers ALTER COLUMN carrier_profile TYPE text USING carrier_profile::text');
                DB::statement('ALTER TABLE carriers ALTER COLUMN carries_this_cargo TYPE text USING carries_this_cargo::text');
            } else {
                // MySQL/SQLite
                $table->string('state_served')->nullable()->change();
                $table->string('carrier_profile')->nullable()->change();
                $table->string('carries_this_cargo')->nullable()->change();
            }
        });
    }

    /**
     * Convert existing string data to JSON format for PostgreSQL
     */
    protected function convertPostgresDataToJson(): void
    {
        $columns = ['state_served', 'carrier_profile', 'carries_this_cargo'];
        
        foreach ($columns as $column) {
            // Get records where the column isn't null and isn't valid JSON
            $records = DB::table('carriers')
                ->whereNotNull($column)
                ->whereRaw("$column::text !~ '^\\[.*\\]\$'") // Doesn't look like JSON array
                ->select(['id', $column])
                ->get();

            foreach ($records as $record) {
                $value = $record->{$column};
                
                // Convert string to array if needed
                if (!json_decode($value)) {
                    // Handle comma-separated values or other formats
                    $arrayValue = str_contains($value, ',') 
                        ? explode(',', $value) 
                        : [$value];
                    
                    // Update with JSON-encoded value
                    DB::table('carriers')
                        ->where('id', $record->id)
                        ->update([$column => json_encode($arrayValue)]);
                }
            }
        }
    }
};

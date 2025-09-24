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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quoted_amount', 20, 2)->nullable();
            $table->date('port_cut_off')->nullable();
            $table->date('ramp_cut_off')->nullable();
            $table->date('earliest_recievable_date')->nullable();
            $table->date('docs_cut_off')->nullable();
            $table->date('original_title_cut_off')->nullable();
            $table->date('eta')->nullable();
            $table->date('esailing_dateta')->nullable();
            $table->timestamps();
        });

         Schema::table('shipments', function (Blueprint $table) {
            $table->string('ocean_shippment_type', 20)->nullable()->after('ocean_total_containers_in_words');
            $table->date('ocean_estimated_loading_date')->nullable()->after('ocean_total_containers_in_words');
            $table->date('booking_date')->nullable()->after('ocean_total_containers_in_words');
            $table->string('ocean_shipment_line')->nullable()->after('ocean_total_containers_in_words');
            $table->integer('quote_accepted_status')->nullable()->after('ocean_total_containers_in_words')->comment('0 = Pending, 1 = Accepted, 2 = Rejected, 3 = Under Review');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('ocean_shippment_type');
            $table->dropColumn('ocean_estimated_loading_date');
            $table->dropColumn('booking_date');
            $table->dropColumn('ocean_shipment_line');
        });
    }
};

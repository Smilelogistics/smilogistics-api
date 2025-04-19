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
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('state_served', 80)->nullable();
            $table->string('code')->nullable();
            $table->string('offices', 50)->nullable();
            $table->string('carrier_number', 100)->nullable();
            $table->string('type')->nullable();
            $table->string('usdot_number')->nullable();
            $table->string('mc_number', 100)->nallable();
            $table->string('scac', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->string('cell_phone', 20)->nullable();
            $table->string('cell_carrier', 15)->nullable();
            $table->integer('carrier_access')->nullable()->default(1)->comment('0 = Block Access, 1 = Active, 2 = Resend OTP');
            $table->integer('show_payment_in_mobile_app')->default(1)->comment('0 = No, 1 = Yes');
            $table->string('office_phone', 20)->nullable();
            $table->string('contact_name', 100)->nullable();
            $table->string('email', 80)->nullable();
            $table->string('primary_address')->nullable();
            $table->string('secondary_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('zip', 15)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('fax_no', 50)->nullable();
            $table->string('toll_free_number', 20)->nullable();
            $table->string('other_contact_info')->nullable();
            $table->integer('no_of_drivers')->nullable();
            $table->integer('power_units')->nullable()->comment("TRUCKS");
            $table->string('other_equipments', 80)->nullable()->comment("CHASIS, REEFERS, VANS ETC");
            $table->string('profile_photo')->nullable();
            $table->string('rating', 100)->nullable()->comment('CARRIER BELONGSTO THIS PAY CATEGORY');
            $table->string('carries_this_cargo', 105)->nullable();
            $table->text('note_about_choices')->nullable();
            $table->date('start_date')->nullable();
            $table->string('tag')->nullable();
            $table->text('flash_note_to_riders_about_this_carrier')->nullable();
            $table->text('flash_note_to_payroll_about_this_carrier')->nullable();
            $table->text('internal_note')->nullable();
            $table->text('notes')->nullable();
            $table->string('insurance_provider', 100)->nullable();
            $table->string('insurance_expire', 80)->nullable();
            $table->text('note_about_coverage')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('paid_via', 100)->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('routing_number', 100)->nullable();
            $table->string('settlement_email_address', 80)->nullable()->comment('SEND SETTLEMENT EMAILS IF DIFFERENT FROM THE MAIN EMAIL ADDRESS');
            $table->string('payment_mailling_address')->nullable()->comment('IF DIFFERENT FROM THE MAIN ADDRESS');
            $table->string('payment_contact', 100)->nullable()->comment('IF DIFFERENT FROM THE MAIN CONTACT');
            $table->text('payment_related_notes')->nullable()->comment('IF DIFFERENT FROM THE MAIN EMAIL');
            $table->string('carrier_smile_id', 50)->nullable()->comment('ASK CARRIER TO LOOK UP THEIR COMPANY PROFILE FOR IDENTIFICATION NUMBER');
            $table->string('data_exchange_option', 100)->nullable()->comment('0 = send Orders Electronically to the carries, 1 = Recieve shipment status from the carrier, 2 = Recieve backup docs from the carrier');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};

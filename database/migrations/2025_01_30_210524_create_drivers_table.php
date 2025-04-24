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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('driver_number', 20)->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->string('driver_phone_carrier', 15)->nullable();
            $table->string('driver_primary_address')->nullable();
            $table->string('driver_secondary_address')->nullable();
            $table->string('driver_country', 20)->nullable();
            $table->string('driver_state', 30)->nullable();
            $table->string('driver_city', 30)->nullable();
            $table->string('driver_zip', 20)->nullable();
            $table->string('office', 20)->nullable();
            $table->integer('driver_type')->nullable()->default(1)->comment('1 = Owner Operator (single Truck), 2 = Company Driver, 3 = Driver for Fleet owner or Fleet owner Himself, 4 = Temporary Driver');
            $table->integer('isAccessToMobileApp')->nullable()->default(1)->comment('0 = Block Access, 1 = Active, 2 = Resend OTP');
            $table->integer('mobile_settings')->nullable()->default(1)->comment('1 =YES, 2 = NO. SHOW PAYMENT IN MOBILE APP');
            $table->text('emergency_contact_info')->nullable();
            $table->date('hired_on')->nullable();
            $table->date('terminated_on')->nullable();
            $table->string('years_of_experience', 10)->nullable();
            $table->json('tags')->nullable();
            $table->string('endorsements')->nullable();
            $table->string('rating')->nullable()->comment('Rate per Template category');
            $table->text('notes_about_the_choices_made')->nullable();
            $table->string('pay_via')->nullable();
            $table->string('company_name_paid_to', 100)->nullable();
            $table->string('employer_identification_number', 50)->nullable();
            $table->string('send_settlements_mail', 80)->nullable()->comment('Email to send settlements to, if different from driver email');
            $table->string('print_settlements_under_this_company', 100)->nullable()->comment('Company to print settlements under, if different from driver company');
            $table->text('flash_notes_to_dispatch')->nullable()->comment('Notes to dispacth to driver during order entry');
            $table->text('flash_notes_to_payroll')->nullable()->comment('Notes to payroll while creating settlement');
            $table->text('internal_notes')->nullable()->comment('Internal Notes');
            $table->string('driver_status', 15)->default('active');

            //form w-9 and insurance details
            $table->string('name_tax_return', 80)->nullable();
            $table->string('different_bussiness_name', 80)->nullable();
            $table->string('wtype', 80)->nullable();
            $table->string('other_type', 80)->nullable();
            $table->string('waddress', 150)->nullable();
            $table->string('wstate', 50)->nullable();
            $table->string('wcity', 50)->nullable();
            $table->string('wzip', 20)->nullable();
            $table->string('wtaxid', 80)->nullable();
            $table->string('wwssn', 80)->nullable();
            $table->string('wwein', 80)->nullable();
            $table->string('wpaid_via', 80)->nullable();
            $table->string('waccountNumber', 20)->nullable();
            $table->string('wroutingNumber')->nullable();
            $table->text('winternal_notes')->nullable();
            $table->string('licensessn', 30)->nullable();
            $table->date('dob')->nullable();
            $table->string('cdlnumber', 80)->nullable();
            $table->string('license_state', 50)->nullable();
            $table->date('cdl_expires')->nullable();
            $table->string('medical_number', 100)->nullable();
            $table->date('medical_expires')->nullable();
            $table->string('twic_number', 80)->nullable();
            $table->date('twic_expires')->nullable();
            $table->string('sealink_number', 80)->nullable();
            $table->date('sealink_expires')->nullable();
            $table->date('annual_mvr')->nullable();
            $table->date('clearing_annual')->nullable();
            $table->date('liability_insurance_expires')->nullable();
            $table->string('insurance_provider', 100)->nullable();
            $table->string('insurance_coverage', 30)->nullable();
            $table->date('date_1')->nullable();
            $table->date('date_2')->nullable();
            $table->date('date_3')->nullable();
            $table->date('date_4')->nullable();
            $table->date('date_5')->nullable();
            $table->date('date_6')->nullable();
            $table->text('license_internal_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};

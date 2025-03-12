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
            $table->string('driver_number')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('driver_phone_carrier')->nullable();
            $table->string('driver_primary_address')->nullable();
            $table->string('driver_secondary_address')->nullable();
            $table->string('driver_country')->nullable();
            $table->string('driver_state')->nullable();
            $table->string('driver_city')->nullable();
            $table->string('driver_zip')->nullable();
            $table->string('office')->nullable();
            $table->integer('driver_type')->nullable()->default(1)->comment('1 = Owner Operator (single Truck), 2 = Company Driver, 3 = Driver for Fleet owner or Fleet owner Himself, 4 = Temporary Driver');
            $table->integer('isAccessToMobileApp')->nullable()->default(1)->comment('0 = Block Access, 1 = Active, 2 = Resend OTP');
            $table->integer('mobile_settings')->nullable()->default(1)->comment('1 =YES, 2 = NO. SHOW PAYMENT IN MOBILE APP');
            $table->text('emergency_contact_info')->nullable();
            $table->date('hired_on')->nullable();
            $table->date('terminated_on')->nullable();
            $table->string('years_of_experience')->nullable();
            $table->string('tags')->nullable();
            $table->string('endorsements')->nullable();
            $table->string('rating')->nullable()->comment('Rate per Template category');
            $table->text('notes_about_the_choices_made')->nullable();
            $table->string('pay_via')->nullable();
            $table->string('company_name_paid_to')->nullable();
            $table->string('employer_identification_number')->nullable();
            $table->string('send_settlements_mail')->nullable()->comment('Email to send settlements to, if different from driver email');
            $table->string('print_settlements_under_this_company')->nullable()->comment('Company to print settlements under, if different from driver company');
            $table->text('flash_notes_to_dispatch')->nullable()->comment('Notes to dispacth to driver during order entry');
            $table->text('flash_notes_to_payroll')->nullable()->comment('Notes to payroll while creating settlement');
            $table->text('internal_notes')->nullable()->comment('Internal Notes');
            $table->string('driver_status')->default('active');
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

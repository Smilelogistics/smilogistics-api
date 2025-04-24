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
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('truck_number', 150)->nullable();
            $table->string('office', 60)->nullable();
            $table->string('make_model', 80)->nullable();
            $table->string('make_year', 20)->nullable();
            $table->string('engine_year', 20)->nullable();
            $table->string('vehicle_number', 80)->nullable();
            $table->string('license_plate_number', 100)->nullable();
            $table->string('license_plate_state', 100)->nullable();
            $table->date('service_start_date')->nullable();
            $table->string('reffered_by', 80)->nullable()->comment('TRUCK REFFERED BY');
            $table->json('tags')->nullable();
            $table->string('endorsements', 100)->nullable();
            $table->text('flash_notes_to_dispatchers')->nullable()->comment('NOTE WILL SHOW UP DURING ORDER ENTRY AND DISPATCH');
            $table->text('flash_notes_to_payroll')->nullable()->comment('NOTE WILL SHOW UP WHILE CREATING SETTLEMENTS');
            $table->text('internal_notes')->nullable();
            $table->integer('createSettlement')->nullable()->default(1)->comment('0 = NO, SETTLEMENT WILL BE CREATED UBDER ITS DRIVER, 1 = YES, SETTLEMENT WILL BE CREATED UNDER THIS TRUCK');
            $table->string('truck_owner_details', 100)->nullable()->comment('LEAVE THIS SECTION BLANK FOR COMPANY TRUCKS');
            $table->string('truck_type', 60)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_alt_biz_name', 100)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_address')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_city', 60)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_state', 80)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_zip', 20)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_phone', 20)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_email', 80)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->integer('isSSNorEIN')->nullable()->default(0)->comment('1 = SSN, 2 = EIN LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('ssn', 100)->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('ein')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('paid_via', 100)->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('routing_number', 50)->nullable();
            $table->text('note_related_to_owner')->nullable();
            //other details --- starts here
            $table->string('registration_expires', 20)->nullable();
            $table->string('annual_inspection_expires', 20)->nullable();
            $table->string('quarterly_inspection_expires', 20)->nullable();
            $table->string('bobtail_insurance_expires', 20)->nullable();
            $table->string('monthly_maintanance_expires', 20)->nullable();
            $table->string('smoke_inspection_expires', 20)->nullable();
            $table->string('overweight_permit_expires', 20)->nullable();
            $table->string('last_paper_work_received', 20)->nullable();
            $table->string('last_log_received', 20)->nullable();
            $table->string('insurance_expires', 20)->nullable();
            $table->string('insurance_provider', 20)->nullable();
            $table->string('insurance_coverage', 20)->nullable();
            $table->text('note_about_insurance')->nullable();
            $table->text('ifta_note')->nullable();
            $table->text('plate_program_note')->nullable();
            $table->text('note_about_other_choices')->nullable();
            $table->string('other_options', 60)->nullable();;
            $table->string('eld_provider', 20)->nullable();
            $table->string('eld_serial_number', 20)->nullable();
            $table->string('tablet_serial_number', 60)->nullable();;
            $table->string('dash_cam_serial_number', 80)->nullable();;
            $table->string('rfid_number', 100)->nullable();;
            $table->string('transponder_number', 100)->nullable();
            $table->string('tablet_provider', 100)->nullable();
            //other details --- ends here
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};

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
            $table->string('truck_number')->nullable();
            $table->string('office')->nullable();
            $table->string('make_model')->nullable();
            $table->string('make_year')->nullable();
            $table->string('engine_year')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('license_plate_number')->nullable();
            $table->string('license_plate_state')->nullable();
            $table->date('service_start_date')->nullable();
            $table->string('reffered_by')->nullable()->comment('TRUCK REFFERED BY');
            $table->string('tags')->nullable();
            $table->string('endorsements')->nullable();
            $table->text('flash_notes_to_dispatchers')->nullable()->comment('NOTE WILL SHOW UP DURING ORDER ENTRY AND DISPATCH');
            $table->text('flash_notes_to_payroll')->nullable()->comment('NOTE WILL SHOW UP WHILE CREATING SETTLEMENTS');
            $table->text('internal_notes')->nullable();
            $table->integer('createSettlement')->nullable()->default(1)->comment('0 = NO, SETTLEMENT WILL BE CREATED UBDER ITS DRIVER, 1 = YES, SETTLEMENT WILL BE CREATED UNDER THIS TRUCK');
            $table->string('truck_owner_details')->nullable()->comment('LEAVE THIS SECTION BLANK FOR COMPANY TRUCKS');
            $table->string('truck_type')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_alt_biz_name')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_address')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_city')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_state')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_zip')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_phone')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('truck_email')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->integer('isSSNorEIN')->nullable()->default(0)->comment('1 = SSN, 2 = EIN LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('ssn')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('ein')->nullable()->comment('LEAVE BLANK FOR COMPANY TRUCKS');
            $table->string('paid_via')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable();
            $table->text('note_related_to_owner')->nullable();
            //other details --- starts here
            $table->string('registration_expires')->nullable();
            $table->string('annual_inspection_expires')->nullable();
            $table->string('quarterly_inspection_expires')->nullable();
            $table->string('bobtail_insurance_expires')->nullable();
            $table->string('monthly_maintanance_expires')->nullable();
            $table->string('smoke_inspection_expires')->nullable();
            $table->string('overweight_permit_expires')->nullable();
            $table->string('last_paper_work_received')->nullable();
            $table->string('last_log_received')->nullable();
            $table->string('insurance_expires')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_coverage')->nullable();
            $table->text('note_about_insurance')->nullable();
            $table->text('ifta_note')->nullable();
            $table->text('plate_program_note')->nullable();
            $table->text('note_about_other_choices')->nullable();
            $table->string('other_options')->nullable();;
            $table->string('eld_provider')->nullable();
            $table->string('eld_serial_number')->nullable();
            $table->string('tablet_serial_number')->nullable();;
            $table->string('dash_cam_serial_number')->nullable();;
            $table->string('rfid_number')->nullable();;
            $table->string('transponder_number')->nullable();
            $table->string('tablet_provider')->nullable();
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

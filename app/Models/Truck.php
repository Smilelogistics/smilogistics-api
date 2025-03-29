<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $table = 'trucks';

    protected $fillable = [
        'branch_id',
        'customer_id',
        'user_id',
        'truck_number',
        'office',
        'make_model',
        'make_year',
        'engine_year',
        'vehicle_number',
        'license_plate_number',
        'license_plate_state',
        'service_start_date',
        'reffered_by',
        'tags',
        'endorsements',
        'flash_notes_to_dispatchers',
        'flash_notes_to_payroll',
        'internal_notes',
        'createSettlement',
        'truck_owner_details',
        'truck_type',
        'truck_alt_biz_name',
        'truck_address',
        'truck_city',
        'truck_state',
        'truck_zip',
        'truck_phone',
        'truck_email',
        'isSSNorEIN',
        'ssn',
        'ein',
        'paid_via',
        'account_number',
        'routing_number',
        'note_related_to_owner',
        'registration_expires',
        'annual_inspection_expires',
        'quarterly_inspection_expires',
        'bobtail_insurance_expires',
        'monthly_maintanance_expires',
        'smoke_inspection_expires',
        'overweight_permit_expires',
        'last_paper_work_received',
        'last_log_received',
        'insurance_expires',
        'insurance_provider',
        'insurance_coverage',
        'note_about_insurance',
        'ifta_note',
        'plate_program_note',
        'note_about_other_choices',
        'other_options',
        'eld_provider',
        'eld_serial_number',
        'tablet_serial_number',
        'dash_cam_serial_number',
        'rfid_number',
        'transponder_number',
        'tablet_provider',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function truckDocs()
    {
        return $this->hasMany(TruckDoc::class);
    }
    
}

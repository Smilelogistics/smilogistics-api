<?php

namespace App\Models;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Carrier extends Model
{
    use Notifiable;
    use FileUploadTrait;

    protected $table = 'carriers';
    protected $casts = [
        'state_served' => 'array',
        'carries_this_cargo' => 'array',
        'carrier_profile' => 'array',
    ];
    protected $fillable = [
        'branch_id',
        'user_id',
        'name',
        'state_served',
        'code',
        'offices',
        'carrier_number',
        'type',
        'usdot_number',
        'mc_number',
        'scac',
        'tax_id',
        'cell_phone',
        'cell_carrier',
        'carrier_access',
        'show_payment_in_mobile_app',
        'office_phone',
        'contact_name',
        'email',
        'primary_address',
        'secondary_address',
        'city',
        'state',
        'zip',
        'country',
        'fax_no',
        'toll_free_number',
        'other_contact_info',
        'no_of_drivers',
        'power_units',
        'other_equipments',
        'profile_photo',
        'rating',
        'carries_this_cargo',
        'note_about_choices',
        'start_date',
        'tag',
        'flash_note_to_riders_about_this_carrier',
        'flash_note_to_payroll_about_this_carrier',
        'internal_note',
        'notes',
        'insurance_provider',
        'insurance_expire',
        'note_about_coverage',
        'payment_terms',
        'paid_via',
        'account_number',
        'routing_number',
        'settlement_email_address',
        'payment_mailling_address',
        'payment_contact',
        'payment_related_notes',
        'payment_method',
        'carrier_smile_id',
        'data_exchange_option',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function billTo()    
    {
        return $this->hasMany(BillTo::class);
    }

    public function consolidatedShipment()
    {
        return $this->hasMany(ConsolidateShipment::class);
    }
    
    /**
     * Update the specified carrier in storage.
     *
     * @param  array $data
     * @return \App\Models\Carrier
     */
    public function updateCarrier(array $data)
    {
        $authUser = Auth::user();
        $branch = $authUser->branch ? $authUser->branch->id : null;
        if ($this->branch_id !== $branch) {
            throw new \Exception('Unauthorized to update this carrier');
        }

        $userData = [];
        $carrierData = $data;

        $userValidator = Validator::make($userData, [
            'name' => 'sometimes|string|max:255',
        ]);
    
        $carrierValidator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'state_served' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50',
            'type' => 'sometimes|string|max:100',
        
            // Carrier Identifiers
            'usdot_number' => 'sometimes|string|max:50',
            'mc_number' => 'sometimes|string|max:50',
            'scac' => 'sometimes|string|max:50',
            'tax_id' => 'sometimes|string|max:50',
            'carrier_number' => 'sometimes|string|max:50',
        
            // Contact Information
            'contact_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'cell_phone' => 'sometimes|string|max:20',
            'cell_carrier' => 'sometimes|string|max:100',
            'office_phone' => 'sometimes|string|max:20',
            'toll_free_number' => 'sometimes|string|max:20',
            'fax_no' => 'sometimes|string|max:20',
        
            // Address Information
            'primary_address' => 'sometimes|string|max:255',
            'secondary_address' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:50',
            'zip' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:50',
        
            // Operational Details
            'offices' => 'sometimes|string|max:255',
            'carrier_access' => 'sometimes|boolean',
            'show_payment_in_mobile_app' => 'sometimes|boolean',
            'no_of_drivers' => 'sometimes|integer|min:0',
            'power_units' => 'sometimes|integer|min:0',
            'other_equipments' => 'sometimes|string',
        
            // Additional Carrier Information
            'rating' => 'sometimes|numeric|between:0,5',
            'carries_this_cargo' => 'sometimes|string',
            'note_about_choices' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'tag' => 'sometimes|string|max:100',
        
            // Flash Notes
            'flash_note_to_riders_about_this_carrier' => 'sometimes|string',
            'flash_note_to_payroll_about_this_carrier' => 'sometimes|string',
            'internal_note' => 'sometimes|string',
            'notes' => 'sometimes|string',
        
            // Insurance Details
            'insurance_provider' => 'sometimes|string|max:255',
            'insurance_expire' => 'sometimes|date',
            'note_about_coverage' => 'sometimes|string',
        
            // Payment Information
            'payment_terms' => 'sometimes|string|max:255',
            'paid_via' => 'sometimes|string|max:100',
            'account_number' => 'sometimes|string|max:50',
            'routing_number' => 'sometimes|string|max:50',
            'settlement_email_address' => 'sometimes|email|max:255',
            'payment_mailling_address' => 'sometimes|string|max:255',
            'payment_contact' => 'sometimes|string|max:255',
            'payment_related_notes' => 'sometimes|string',
            'payment_method' => 'sometimes|string|max:100',
            'carrier_smile_id' => 'sometimes|string|max:50',
            'data_exchange_option' => 'sometimes|string|max:100',
        
            // File Upload
            'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        

        if ($userValidator->fails()) {
            throw new ValidationException($userValidator);
        }
    
        if ($carrierValidator->fails()) {
            throw new ValidationException($carrierValidator);
        }

          
        return DB::transaction(function () use ($userData, $carrierData) {
            if (!empty($userData)) {
                $this->user->update($userData);
            }
    
            if (!empty($carrierData)) {
                $this->update($carrierData);
            }

            $this->refresh();
    
            return $this;
        });
    }

    public function scopeInUserBranch($query)
    {
        $user = Auth::user();
        return $query->where('branch_id', $user->branch_id);
    }

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function carrierDocs(){
        return $this->hasMany(CarrierDocs::class);
    }

    public function carrierInsurance(){
        return $this->hasMany(CarrierInsurance::class);
    }

}

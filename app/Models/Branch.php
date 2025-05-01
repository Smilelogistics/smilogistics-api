<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'user_id',
        'branch_code',
        'address',
        'about_us',
        'phone',
        'parcel_tracking_prefix',
        'invoice_prefix',
        'invoice_logo',
        'currency',
        'copyright',
        'paystack_publicKey',
        'paystack_secretKey',
        'FLW_pubKey',
        'FLW_secKey',
        'Razor_pubKey',
        'Razor_secKey',
        'stripe_pubKey',
        'stripe_secKey',
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'enable_2fa',
        'enable_email_otp',
        'enable_recaptcha',
        'tax', // Added (missing in original fillable array)
        'custom_duties_charge', // Added (missing in original fillable array)
        'shipment_insurance', // Added (missing in original fillable array)
        'discount', // Added (missing in original fillable array)
        'db_backup',
        'app_theme',
        'app_secondary_color',
        'app_text_color',
        'app_alt_color',
        'logo1',
        'logo2',
        'logo3',
        'mpg',
        'business_status',
        'subscription_count',
        'subscription_end_date',
        'subscription_status',
        'subscription_type',
        'subscription_date',
        'isSubscribed',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->subscription_date)) {
                $user->subscription_date = now()->toDateString();
            }

            if (empty($user->subscription_end_date)) {
                $user->subscription_end_date = now()->addDays(30)->toDateString();
            }
        });
    }
    
    protected $casts = [
        'subscription_date' => 'date',
        'subscription_end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->hasMany(Customer::class);
    }

    public function driver()
    {
        return $this->hasMany(Driver::class);
    }
    public function consolidatedShipment()
    {
        return $this->hasMany(ConsolidateShipment::class);
    }

    public function shipment()
    {
        return $this->hasMany(Shipment::class);
    }
    public function billTo()    
    {
        return $this->hasMany(BillTo::class);
    }

    public function agency()
    {
        return $this->hasMany(Agency::class);
    }

    public function consolidateShipment()
    {
        return $this->hasMany(ConsolidateShipment::class);
    }

}

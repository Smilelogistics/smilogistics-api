<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use Notifiable;
    
    protected $fillable = [
        'id',
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
        'base_rate',
        'base_fee',
        'handling_fee',
        'business_status',
        'subscription_count',
        'subscription_end_date',
        'subscription_status',
        'subscription_type',
        'subscription_date',
        'isSubscribed',
        'price_per_mile',
        'price_per_gallon',
        'short_name',
        'long_name',

       'trucking_bank_name',
      'trucking_account_name',
      'trucking_account_number',
      'trucking_routing',
      'trucking_zelle',
      'trucking_pay_cargo',
      'ocean_bank_name',
      'ocean_account_name',
      'ocean_account_number',
      'ocean_routing',
      'ocean_zelle',
      'max_length',
      'max_height',
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
    public function settlement()
    {
        return $this->hasMany(Settlement::class);
    }

    public function offices()
    {
        return $this->hasMany(OfficeLocation::class);
    }

    // public function isSubscribed(): bool
    // {
    //     return $this->isSubscribed == 1;
    // }

     public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
    }

    public function hasFeatureAccess(string $featureSlug): bool
    {
        $subscription = $this->activeSubscription();
        
        if (!$subscription) {
            return false;
        }

        return $subscription->plan->features()
            ->where('slug', $featureSlug)
            ->exists();
    }

}

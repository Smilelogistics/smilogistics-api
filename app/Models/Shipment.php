<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Notifications\NewShipmentCreated;

class Shipment extends Model
{
    use Notifiable;
    protected $table = 'shipments';

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
    ];

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'shipment_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
    public function creatorDriver()
    {
        return $this->belongsTo(Driver::class, 'created_by_driver_id');
    }
    public function shipmentTrack() {
        return $this->hasMany(ShipmentTrack::class, 'shipment_id');
    }
    public function shipmentUploads()
    {
        return $this->hasMany(ShipmentUploads::class, 'shipment_id');
    }
    public function shipmentCharges()
    {
        return $this->hasMany(ShipmentCharge::class);
    }
    public function shipmentExpenses()
    {
        return $this->hasMany(ShipmentExpense::class, 'shipment_id');
    }
    public function shipmentNotes()
    {
        return $this->hasMany(ShipmentNote::class, 'shipment_id');
    }

    public function shipmentContainers()
    {
        return $this->hasMany(ShipmentContainer::class, 'shipment_id');
    }
    public function consolidatedShipment()
    {
        return $this->belongsToMany(ConsolidatedShipment::class, 'shipment_consolidations');
    }
    public function billTo()
    {
        return $this->hasMany(BillTo::class, 'shipment_id');
    }

    public function quote()
    {
        return $this->hasOne(Quote::class, 'shipment_id');
    }
      public function pickups()
    {
        return $this->hasMany(ShipmentLocation::class)->where('type', 'pickup')->orderBy('sequence');
    }

    public function dropoffs()
    {
        return $this->hasMany(ShipmentLocation::class)->where('type', 'dropoff')->orderBy('sequence');
    }

    public function allLocations()
    {
        return $this->hasMany(ShipmentLocation::class)->orderBy('type')->orderBy('sequence');
    }
    // public static function generateTrackingNumber() {
    //     do {
    //         $trackingNumber = random_int(1000000000, 9999999999);
    //     } while (DB::table('shipments')->where('shipment_tracking_number', $trackingNumber)->exists()); 
    
    //     return $trackingNumber;
    // }

    public static function generateTrackingNumber()
    {
        $prefix = 'SHIP-';

        do {
            $number = random_int(1000000000, 9999999999);
            $trackingNumber = $prefix . $number;
        } while (DB::table('shipments')->where('shipment_tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }


    public static function generateUniqueCode($table, $column, $prefix='', $digits = 10)
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $number = random_int(pow(10, $digits-1), pow(10, $digits)-1);
            $code = $prefix . $number;
            
            $exists = DB::table($table)
                    ->where($column, $code)
                    ->exists();
            
            if (!$exists) {
                return $code;
            }
            
            $attempt++;
        } while ($attempt < $maxAttempts);

        throw new \RuntimeException("Failed to generate unique code after {$maxAttempts} attempts");
    }

    protected static function booted()
    {
        static::created(function ($shipment) {
            $user = User::find($shipment->user_id);
            if ($user) {
                $user->notify(new NewShipmentCreated($shipment));
            }
        });
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            //if (empty($shipment->booking_date)) {
                $shipment->booking_date =  Carbon::now()->toDateString();
           // }
        });
    }

}

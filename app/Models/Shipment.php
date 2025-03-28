<?php

namespace App\Models;

use App\Models\User;
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
        return $this->belongsTo(User::class, 'driver_id');
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
        return $this->hasMany(ShipmentCharge::class, 'shipment_id');
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
    public static function generateTrackingNumber() {
        do {
            $trackingNumber = random_int(1000000000, 9999999999);
        } while (DB::table('shipments')->where('shipment_tracking_number', $trackingNumber)->exists()); 
    
        return $trackingNumber;
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
}

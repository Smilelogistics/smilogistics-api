<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Models\ConsolidateShipmentDoc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsolidateShipment extends Model
{

    use HasFactory;
    protected $table = 'consolidate_shipments';
    protected $guarded = [];

    public function customer() {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function carrier() {
        return $this->belongsTo(User::class, 'carrier_id');
    }

    public function driver() {
        return $this->belongsTo(User::class, 'driver_id');
    }

      public function creatorDriver()
        {
            return $this->belongsTo(Driver::class, 'created_by_driver_id');
        }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function documents() {
        return $this->hasMany(ConsolidateShipmentDoc::class);
    }

    public function consolidateShipmentCharges() {
        return $this->hasMany(ConsolidateShipmentCharges::class);
    }

    public static function generateTrackingNumber() {
        do {
            $trackingNumber = random_int(1000000000, 9999999999);
        } while (DB::table('consolidate_shipments')->where('consolidate_tracking_number', $trackingNumber)->exists()); 
    
        return $trackingNumber;
    }
}

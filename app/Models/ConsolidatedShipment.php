<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class ConsolidatedShipment extends Model
{
    protected $table = 'consolidated_shipments';

    protected $guarded = [];

    public function shipments()
    {
        return $this->belongsToMany(Shipment::class, 'shipment_consolidations');
    }
    

    public static function generateTrackingNumber()
    {
        do {
            $trackingNumber = strtoupper(Str::random(10));
        } while (self::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }
}

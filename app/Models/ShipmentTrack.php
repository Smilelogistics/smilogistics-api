<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentTrack extends Model
{
    protected $table = 'shipment_tracks';
    protected $fillable = ['shipment_id', 'consolidate_shipment_id', 'tracking_number', 'location', 'status'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function consolidateShipment()
    {
        return $this->belongsTo(ConsolidatedShipment::class);
    }

}

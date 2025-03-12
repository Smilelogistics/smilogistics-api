<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentConsolidation extends Model
{
    protected $table = 'shipment_consolidations';

    protected $guarded = [];

    // protected $fillable = [
    //     'consolidated_shipment_id',
    //     'shipment_id',
    // ];

    public function consolidatedShipment()
    {
        return $this->belongsTo(ConsolidatedShipment::class, 'consolidated_shipment_id');
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}

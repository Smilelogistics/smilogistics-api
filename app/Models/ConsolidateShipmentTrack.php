<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsolidateShipmentTrack extends Model
{

    use HasFactory;

    protected $table = 'consolidate_shipment_tracks';
    protected $guarded = [];

    
    public function consolidate()
    {
        return $this->belongsTo(ConsolidateShipment::class);
    }
}

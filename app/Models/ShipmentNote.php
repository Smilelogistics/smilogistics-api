<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentNote extends Model
{
    protected $table = 'shipment_notes';

    protected $fillable = [
        'shipment_id',
        'branch_id',
        'note',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}

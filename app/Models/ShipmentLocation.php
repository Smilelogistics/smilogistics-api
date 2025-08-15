<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentLocation extends Model
{

    use HasFactory;

       protected $fillable = [
        'shipment_id',
        'type',
        'sequence',
        'pick_up_type',
        'drop_at_type',
        'location_name',
        'address',
        'city',
        'state',
        'zip',
        'latitude',
        'longitude',
        'appt_date',
        'no_latter_than_date',
        'no_latter_than_time'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'appt_date' => 'date',
        'no_latter_than_date' => 'date',
        'sequence' => 'integer'
    ];

      public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function getCoordinatesAttribute()
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude
        ];
    }
}

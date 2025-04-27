<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsolidateShipmentCharges extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function consolidateShipment()
    {
        return $this->belongsTo(ConsolidateShipment::class);
    }
}

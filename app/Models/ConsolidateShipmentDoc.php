<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsolidateShipmentDoc extends Model
{

    use HasFactory;

    protected $table = 'consolidate_shipment_docs';
    protected $guarded = [];

    public function consolidateShipment() {
        return $this->belongsTo(ConsolidateShipment::class);
    }
}

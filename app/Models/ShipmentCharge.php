<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentCharge extends Model
{
    protected $table = 'shipment_charges';
   // protected $fillable =  ['branch_id', 'charge_type', 'comment', 'units', 'rate', 'amount', 'discount', 'internal_notes', 'billed', 'invoice_number', 'invoice_date', 'total', 'net_total', 'shipment_id'];

   protected $guarded = [];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}

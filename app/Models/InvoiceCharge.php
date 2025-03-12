<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceCharge extends Model
{
    protected $table = 'invoice_charges';
    protected $fillable = [
        'invoice_id',
        'load_number',
        'charge_type',
        'comment',
        'units',
        'unit_rate',
        'amount',
        'discount',
        'internal_notes',
        'general_internal_notes',
        'tags',
        'isAccessorial',
        'total',
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}

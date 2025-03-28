<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillTo extends Model
{
    protected $table = 'bill_tos';
    protected $guarded = [];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

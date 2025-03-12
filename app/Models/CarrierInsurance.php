<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarrierInsurance extends Model
{
    protected $table = 'carrier_insurances';
    protected $fillable = ['carrier_id', 'coverage', 'amount', 'policy_number', 'expires'];

    public function carrier(){
         return $this->belongsTo(Carrier::class, 'carrier_id');
         }
}

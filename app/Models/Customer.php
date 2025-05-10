<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
     use Notifiable;
      
    protected $table = 'customers';

    protected $guarded = [];

    protected $casts = [
        'tag' => 'array',
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class);
    }   

    public function consolidatedShipment()
    {
        return $this->hasMany(ConsolidateShipment::class);
    }
    public function shipment()
    {
        return $this->hasMany(Shipment::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function carrier()
    {
        return $this->hasMany(Carrier::class);
    }

    public function documents()
    {
        return $this->hasMany(CustomerDocs::class);
    }

}

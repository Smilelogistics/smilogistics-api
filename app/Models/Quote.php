<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{

    use HasFactory;

    protected $table = 'quotes';
    protected $guarded = [];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

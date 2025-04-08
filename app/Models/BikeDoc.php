<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BikeDoc extends Model
{

    protected $table = 'bike_docs';
    protected $guarded = [];

    public function bike() {
        return $this->belongsTo(Bike::class);
    }
    use HasFactory;
}

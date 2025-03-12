<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TruckDoc extends Model
{
    protected $table = 'truck_docs';
    protected $fillable = [
        'truck_id',
        'file',
        'file_title'
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }
}

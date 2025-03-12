<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TruckDriver extends Model
{
    protected $table = 'truck_drivers';

    protected $fillable = ['truck_id', 'driver_id', 'quick_notes', 'note_to_dispatcher'];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarrierDocs extends Model
{
    protected $table = 'carrier_docs';

    protected $fillable = [
        'branch_id',
        'carrier_id',
        'file',
        'file_title',
    ];
    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }
    
}

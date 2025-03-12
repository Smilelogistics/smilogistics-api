<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentUploads extends Model
{
    protected $table = 'shipment_uploads';

    protected $fillable = [
        'shipment_id',
        'file_name',
        'file_path',
        'file_type',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}

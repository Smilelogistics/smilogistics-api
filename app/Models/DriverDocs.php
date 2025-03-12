<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverDocs extends Model
{
    protected $table = 'driver_docs';

    protected $fillable = [
        'driver_id',
        'file',
        'file_title',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

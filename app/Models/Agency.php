<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    //protected $table = 'agencies';

    protected $fillable = [
        'user_id',	
        'branch_id',	
        'agency_phone',	
        'agency_address',	
        'agency_country',	
        'agency_state',	
        'agency_city',	
        'agency_zip',	
        'agency_status',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}

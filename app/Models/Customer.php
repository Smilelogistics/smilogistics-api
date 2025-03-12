<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }   

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

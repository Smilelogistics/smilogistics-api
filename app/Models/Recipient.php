<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    protected $table = 'recipients';

    protected $fillable = ['fname', 'lname', 'mname', 'email', 'phone','alt_phone', 'status'];

    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}

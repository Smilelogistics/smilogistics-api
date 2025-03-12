<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipientsAddress extends Model
{
    protected $table = 'recipients_address';

    protected $fillable = [
        'recipients_id',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
    ];

    public function recipient() {
        return $this->belongsTo(Recipients::class, 'recipients_id');
    }
}

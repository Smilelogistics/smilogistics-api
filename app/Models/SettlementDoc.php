<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementDoc extends Model
{

    use HasFactory;
     protected $guarded = [];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsDescription extends Model
{

    use HasFactory;

    protected $table = 'goods_descriptions';

    protected $guarded = [];

    public function shipment() {
        return $this->belongsTo(Shipment::class);
    }
}

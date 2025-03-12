<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDoc extends Model
{
    protected $fillable = [
        'invoice_id',
        'file',
        'file_title',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}

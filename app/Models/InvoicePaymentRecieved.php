<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePaymentRecieved extends Model
{
    protected $table = 'invoice_payment_recieveds';

    protected $fillable = [
        'invoice_id',
        'payment_date',
        'payment_method',
        'check_number',
        'amount',
        'processing_fee_percent',
        'processing_fee_flate_rate',
        'notes',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}

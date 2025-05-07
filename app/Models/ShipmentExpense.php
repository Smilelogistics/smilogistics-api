<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentExpense extends Model
{
    protected $table = 'shipment_expenses';

    protected $fillable = [
        'shipment_id',
        'branch_id',
        'expense_type',
        'units',
        'rate',
        'amount',
        'discount',
        'credit_reimbursement_amount',
        'internal_notes',
        'vendor_invoice_name',
        'vendor_invoice_number',
        'payment_reference_note',
        'billed',
        'paid',
        'paid_date',
        'disputed_note',
        'disputed',
        'disputed_amount',
        'disputed_date',
        'net_expense',
        'expense_total',
        'credit_total'
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}

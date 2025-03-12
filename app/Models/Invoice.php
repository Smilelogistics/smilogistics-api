<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $fillable = [
        'shipment_id',
        'user_id',
        'branch_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'invoice_prefix',
        'isFactored',
        'override_default_company',
        'invoice_type',
        'invoice_note',
        'office',
        'bill_to',
        'bill_to_note',
        'invoice_terms',
        'invoice_due_date',
        'attention_invoice_to',
        'note_bill_to_party',
        'loads_on_invoice',
        'reference_number',
        'po_number',
        'booking_number',
        'bill_of_landing_number',
        'move_date',
        'trailer',
        'container',
        'chasis',
        'load_weight',
        'commodity',
        'no_of_packages',
        'from_address',
        'to_address',
        'stop_address',
        'credit_memo',
        'credit_amount',
        'credit_date',
        'credit_note',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function invoicecharges()
    {
        return $this->hasMany(InvoiceCharge::class);
    }
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
    public function invoicedocs()
    {
        return $this->hasMany(InvoiceDoc::class);
    }
    public function invoicepayments()
    {
        return $this->hasMany(InvoicePaymentRecieved::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}

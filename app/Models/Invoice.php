<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

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
        'net_total',
        'total_discount',
        'total_repayment_amount',
        'remaining_balance',
        'status',
      
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

    public function paymentRecord()
    {
        return $this->hasMany(InvoicePaymentRecord::class);
    }

    
    public static function generateInvoiceNumber()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $lastInvoice = DB::table('invoices')->where('branch_id', $branchId)->orderBy('id', 'desc')->first();

        // Extract the number and increment it
        $lastNumber = $lastInvoice ? intval($lastInvoice->invoice_number) : 0;
        $newNumber = $lastNumber + 1;

        // Format it with leading zeros (up to 4 digits)
        $invoiceNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness by checking the database
        while (DB::table('invoices')->where('invoice_number', $invoiceNumber)->exists()) {
            $newNumber++;
            $invoiceNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        }

        return $invoiceNumber;
    }

}

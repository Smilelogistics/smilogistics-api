<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_prefix')->nullable();
            $table->integer('isFactored')->nullable()->default(0)->comment('0 = NO, 1 = YES');
            $table->string('override_default_company')->nullable();
            $table->string('invoice_type')->nullable();
            $table->text('invoice_note')->nullable();
            $table->string('office')->nullable();
            $table->integer('bill_to')->nullable()->comment('populate from BILLTO table');
            $table->text('bill_to_note')->nullable();
            $table->string('invoice_terms')->nullable();
            $table->string('invoice_due_date')->nullable();
            $table->string('attention_invoice_to')->nullable();
            $table->text('note_bill_to_party')->nullable()->comment('NOTE WILL SHOW UP ON INVOICE');
            $table->string('loads_on_invoice')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('po_number')->nullable();
            $table->string('booking_number')->nullable();
            $table->string('bill_of_landing_number')->nullable();
            $table->string('move_date')->nullable();
            $table->string('trailer')->nullable();
            $table->string('container')->nullable();
            $table->string('chasis')->nullable();
            $table->string('load_weight')->nullable();
            $table->string('commodity')->nullable();
            $table->string('no_of_packages')->nullable();
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->string('stop_address')->nullable()->comment('IF THE SHIPMENT HAS MULTIPLE STOPS');
            //Apply credit from credit memos starts here
            $table->string('credit_memo')->nullable();
            $table->string('credit_amount')->nullable();
            $table->string('credit_date')->nullable();
            $table->text('credit_note')->nullable();
            //Apply credit from credit memos ends here
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

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
            $table->string('invoice_prefix', 20)->nullable();
            $table->integer('isFactored')->nullable()->default(0)->comment('0 = NO, 1 = YES');
            $table->string('override_default_company', 100)->nullable();
            $table->string('invoice_type', 20)->nullable();
            $table->text('invoice_note')->nullable();
            $table->string('office', 20)->nullable();
            $table->integer('bill_to')->nullable()->comment('populate from BILLTO table');
            $table->text('bill_to_note')->nullable();
            $table->text('invoice_terms')->nullable();
            $table->string('invoice_due_date')->nullable();
            $table->string('attention_invoice_to', 100)->nullable();
            $table->text('note_bill_to_party')->nullable()->comment('NOTE WILL SHOW UP ON INVOICE');
            $table->string('loads_on_invoice', 100)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('po_number', 100)->nullable();
            $table->string('booking_number', 100)->nullable();
            $table->string('bill_of_landing_number', 100)->nullable();
            $table->string('move_date', 30)->nullable();
            $table->string('trailer', 80)->nullable();
            $table->string('container', 100)->nullable();
            $table->string('chasis', 100)->nullable();
            $table->string('load_weight', 20)->nullable();
            $table->string('commodity')->nullable();
            $table->integer('no_of_packages')->nullable();
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->string('stop_address')->nullable()->comment('IF THE SHIPMENT HAS MULTIPLE STOPS');
            //Apply credit from credit memos starts here
            $table->text('credit_memo')->nullable();
            $table->string('credit_amount', 30)->nullable();
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

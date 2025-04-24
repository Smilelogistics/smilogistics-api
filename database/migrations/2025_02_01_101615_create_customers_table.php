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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_email', 80)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_primary_address')->nullable();
            $table->string('customer_secondary_address')->nullable();
            $table->string('customer_country', 20)->nullable();
            $table->string('customer_state', 20)->nullable();
            $table->string('customer_city', 20)->nullable();
            $table->string('customer_zip', 10)->nullable();
            $table->string('customer_code', 50)->nullable();
            $table->string('customer_type', 50)->nullable();
            $table->string('customer_office', 20)->nullable();
            $table->string('customer_sales_rep', 50)->nullable();
            $table->integer('isSubAccount')->default(0)->nullable();
            $table->string('subAccount_of', 50)->nullable();
            $table->integer('create_invoices_under_this_parent')->default(0)->nullable();
            $table->integer('create_invoices_under_accounting_book')->default(0)->nullable()->comment('Invoice will be created under the accounting book not the parent');
            $table->string('fax_no', 50)->nullable();
            $table->string('toll_free', 50)->nullable();
            $table->text('other_notes')->nullable();
            $table->string('credit_limit', 50)->nullable();
            $table->string('alert_percentage', 30)->nullable();
            $table->string('outstanding_balance', 80)->nullable();
            $table->string('send_invoice_under_this_company', 80)->nullable();
            $table->string('account_code', 80)->nullable()->comment('Accounting Code, if different from account Recievable');
            $table->text('invoice_footer_note')->nullable()->comment('Accounting Code, if different from standard footer note');
            $table->integer('isFactoredInvoice')->default(0)->nullable();
            $table->string('factoringCompany', 80)->nullable()->comment('Company name of the factoring company, if isfactored is = 1');
            $table->integer('isPrepaid')->nullable()->default(0)->comment('0 = No, 1 = Yes, a prepaying customer');	
            $table->integer('isNonBillable')->nullable()->default(0)->comment('0 = No, 1 = Yes, a non billable customer');
            $table->integer('exportToAccountin')->nullable()->default(0)->comment('0=NO, 1=YES, Export to Accounting in next Run');
            $table->text('flash_note_for_drivers')->nullable()->comment('Notes will show up when entering order');
            $table->text('flash_note_for_accounting')->nullable()->comment('Notes will show up in the charges section');
            $table->date('start_date')->nullable();
            $table->json('tag')->nullable();
            $table->text('internal_note')->nullable();
            $table->text('note')->nullable();
            $table->string('customer_status', 20)->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

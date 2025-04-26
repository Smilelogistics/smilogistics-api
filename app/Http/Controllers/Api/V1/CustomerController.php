<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Mail\newCustomerMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\StoreCustomerRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with(['branch', 'user'])->get();
        return response()->json(['customers' => $customers], 200);
    }

    public function show($id)
    {
        $customer = Customer::with(['branch', 'user'])->findOrFail($id);
        return response()->json(['customer' => $customer], 200);
    }

    public function store(StoreCustomerRequest $request)
    {
        $authUser = auth()->user();
        $branchId = $authUser->branch ? $authUser->branch->id : null;
        $validatedData = $request->validated();

        $user = User::create([
            'branch_id' => $branchId,
            'fname' => $request->customer_name,
            'email' => $request->customer_email,
            'user_type' => 'customer',
            'password' => Hash::make('12345678'),
        ]);

        $user->addRole('customer');

        Mail::to($user->email)->send(new newCustomerMail($user));

        $customer = Customer::create([
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'customer_primary_address' => $request->customer_primary_address,
            'customer_secondary_address' => $request->customer_secondary_address,
            'customer_code' => $request->customer_code,
            'customer_sales_rep' => $request->customer_sales_rep,
            'customer_office' => $request->customer_office,
            'customer_type' => $request->customer_type,
            'customer_city' => $request->customer_city,
            'customer_state' => $request->customer_state,
            'customer_zip' => $request->customer_zip,
            'customer_country' => $request->customer_country,
            'fax_no' => $request->fax_no,
            'toll_free' => $request->toll_free,
            'other_notes' => $request->other_notes,
            'credit_limit' => $request->credit_limit,
            'alert_percentage' => $request->alert_percentage,
            'outstanding_balance' => $request->outstanding_balance,
            'due_date' => $request->due_date,
            'send_invoice_under_this_company' => $request->send_invoice_under_this_company,
            'account_code' => $request->account_code,
            'invoice_footer_note' => $request->invoice_footer_note,
            'isSubAccount' => $request->boolean('isSubAccount'),
            'create_invoices_under_this_parent' => $request->create_invoices_under_this_parent,
            'subAccount_of' => $request->subAccount_of,
            'factoringCompany' => $request->factoringCompany,
            'isFactoredInvoice' => $request->boolean('isFactoredInvoice'),
            'isPrepaid' => $request->boolean('isPrepaid'),
            'isNonBillable' => $request->boolean('isNonBillable'),
            'flash_note_for_accounting' => $request->flash_note_for_accounting,
            'payment_terms' => $request->payment_terms,
            'notes' => $request->notes,
            'print_settlements_under_this_company' => $request->boolean('print_settlements_under_this_company'),
            'flash_notes_to_dispatch' => $request->flash_notes_to_dispatch,
            'flash_notes_to_payroll' => $request->flash_notes_to_payroll,
            'internal_notes' => $request->internal_notes,
        ]);

        if ($request->hasFile('file_path')) {
            $files = $request->file('file_path');
        
            // Normalize to array (even if it's one file)
            $files = is_array($files) ? $files : [$files];
        
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'Smile_logistics/Customers',
                    ]);
        
                    $customer->documents()->updateOrCreate(
                        [ 
                            'customer_id' => $customer->id],
                        [
                            'file_path' => $uploadedFile->getSecurePath(),
                            'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Customer Created successfully',
            'customer' => $customer
        ], 200);
    }


    public function update(StoreCustomerRequest $request, $id)
    {
        
    }
}

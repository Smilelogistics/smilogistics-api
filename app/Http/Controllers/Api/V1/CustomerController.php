<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Mail\newCustomerMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CustomerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $customers = Customer::with(['branch', 'user', 'documents'])
            ->latest()
            ->where('branch_id', $branchId)
            ->get();
        return response()->json(['customers' => $customers], 200);
    }

    public function show($id)
    {
        $customer = Customer::with(['branch', 'user', 'documents'])->findOrFail($id);
        return response()->json(['customer' => $customer], 200);
    }

    public function store(StoreCustomerRequest $request)
    {
        $authUser = auth()->user();
        $branchId = $authUser->branch ? $authUser->branch->id : null;
        $validatedData = $request->validated();

        DB::beginTransaction();

        $user = User::create([
            'branch_id' => $branchId,
            'fname' => $request->customer_name,
            'email' => $request->customer_email,
            'user_type' => 'customer',
            'password' => Hash::make('12345678'),
        ]);

        $user->addRole('customer');

        //Mail::to($user->email)->send(new newCustomerMail($user));

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
            'start_date' => $request->start_date,
            'send_invoice_under_this_company' => $request->send_invoice_under_this_company,
            'account_code' => $request->account_code,
            'invoice_footer_note' => $request->invoice_footer_note,
            'isSubAccount' => $request->isSubAccount,
            'create_invoices_under_this_parent' => $request->create_invoices_under_this_parent,
            'subAccount_of' => $request->subAccount_of,
            'factoringCompany' => $request->factoringCompany,
            'isFactoredInvoice' => $request->isFactoredInvoice,
            'isPrepaid' => $request->isPrepaid,
            'isNonBillable' => $request->isNonBillable,
            'flash_note_for_accounting' => $request->flash_note_for_accounting,
            'note' => $request->notes,
            'tag' => $request->tag,
            //'print_settlements_under_this_company' => $request->boolean('print_settlements_under_this_company'),
            'flash_note_for_drivers' => $request->flash_notes_to_dispatch,
            //'flash_notes_to_payroll' => $request->flash_notes_to_payroll,
            'internal_note' => $request->internal_notes,
            'customer_status' => 'active',
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

        DB::commit();

        return response()->json([
            'message' => 'Customer Created successfully',
            'customer' => $customer
        ], 200);
    }


    public function update(UpdateCustomerRequest $request, $id)
{
    $customer = Customer::findOrFail($id);
    $data = $request->validated();
    if (!empty($data)) {
        $customer->update($data);
    }

    // Handle file upload separately
    if ($request->hasFile('file_path')) {
        $file = $request->file('file_path');

        if ($file->isValid()) {
            $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'Smile_logistics/Customers',
            ]);

            $customer->documents()->updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'file_path' => $uploadedFile->getSecurePath(),
                    'public_id' => $uploadedFile->getPublicId()
                ]
            );
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Customer Updated successfully',
        'customer' => $customer
    ]);
}

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return response()->json([
            'message' => 'Customer Deleted successfully',
            'customer' => $customer
        ], 200);
    }
}

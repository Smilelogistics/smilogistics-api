<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BranchController extends Controller
{
    public function Dashboard(Request $request)
    {
        $branch = Branch::all();
        return response()->json($branch);
    }
    public function getMPG()
    {
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();

        $mpg = Branch::where('id', $branchId)->value('mpg');
        return response()->json($mpg);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
                "user_id" => "required|integer|exists:users,id",
                "branch_code" => "required|string|max:10",
                "address" => "nullable|string|max:255",
                "about_us" => "nullable|string",
                "phone" => "required|string|max:20",
                "parcel_tracking_prefix" => "nullable|string|max:5",
                "invoice_prefix" => "nullable|string|max:5",
                "invoice_logo" => "nullable|string",
                "currency" => "required|string|max:3",
                "copyright" => "nullable|string|max:255",
                "paystack_publicKey" => "nullable|string",
                "paystack_secretKey" => "nullable|string",
                "FLW_pubKey" => "nullable|string",
                "FLW_secKey" => "nullable|string",
                "Razor_pubKey" => "nullable|string",
                "Razor_secKey" => "nullable|string",
                "stripe_pubKey" => "nullable|string",
                "stripe_secKey" => "nullable|string",
                "mail_driver" => "nullable|string",
                "mail_host" => "nullable|string",
                "mail_port" => "nullable|integer",
                "mail_username" => "nullable|string",
                "mail_password" => "nullable|string",
                "mail_encryption" => "nullable|string",
                "mail_from_address" => "nullable|email",
                "mail_from_name" => "nullable|string",
                "enable_2fa" => "boolean",
                "enable_email_otp" => "boolean",
                "enable_recaptcha" => "boolean",
                "tax" => "nullable|numeric|min:0",
                "custom_duties_charge" => "nullable|numeric|min:0",
                "shipment_insurance" => "nullable|numeric|min:0",
                "discount" => "nullable|numeric|min:0|max:100",
                "db_backup" => "boolean",
                "app_theme" => "nullable|string",
                "app_secondary_color" => "nullable|string",
                "app_text_color" => "nullable|string",
                "app_alt_color" => "nullable|string",
                "logo1" => "nullable|string",
                "logo2" => "nullable|string",
                "logo3" => "nullable|string",
                "business_status" => "nullable|string|in:active,inactive"
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = Branch::findOrFail($id);
        //dd($branch);
        $branch->update($request->all());

        return response()->json($branch);
    }
}

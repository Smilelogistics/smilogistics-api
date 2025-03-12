<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Recipient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RecipientsController extends Controller
{
    public function postRecipients(Request $request) {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'addresses' => 'required', // Required but can be single or multiple
            'addresses.address' => 'nullable|string|max:255', // Single address case
            'addresses.country' => 'nullable|string|max:255',
            'addresses.state' => 'nullable|string|max:255',
            'addresses.zip' => 'nullable|string|max:255',
            'addresses.city' => 'nullable|string|max:255',
            'addresses.*.address' => 'nullable|string|max:255', // Multiple addresses case
            'addresses.*.country' => 'nullable|string|max:255',
            'addresses.*.state' => 'nullable|string|max:255',
            'addresses.*.zip' => 'nullable|string|max:255',
            'addresses.*.city' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $recipient = Recipient::create([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'mname' => $request->mname,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);
        if ($recipient) {
            if (isset($request->addresses['address'])) {
                $recipient->addresses()->create($request->addresses);
            } else {
                foreach ($request->addresses as $address) {
                    $recipient->addresses()->create($address);
                }
            }
        }
        return response()->json(['recipient' => $recipient], 200);
    }

    
    public function getRecipients() {
        $recipients = Recipient::with('customer')->get();
        return response()->json(['recipients' => $recipients], 200);
    }  
}

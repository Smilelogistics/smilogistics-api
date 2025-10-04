<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Quote;
use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Mail\CustomerQuoteMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Notifications\customerQuoteNotification;
use App\Notifications\BookingStatusUpdateNotification;

class QuotesController extends Controller
{
    public function index()
    {
        $quotes = Quote::with('shipment')->get();
        return response()->json(['quotes' => $quotes], 200);
    }

    public function show($id)
    {
        $quote = Quote::with('shipment')->find($id);
        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }
        return response()->json(['quote' => $quote], 200);
    }

    public function update(Request $request, $id)
    {
        $quote = Quote::with(['shipment.pickups', 'shipment.branch', 'shipment.carrier', 'shipment.dropoffs', 'shipment.customer'])->find($id);
        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }
        $quote->update([
            'user_id' => auth()->user()->id,
            'quoted_amount' => $request->input('quoted_amount'),
            'eta' => $request->input('eta'),
            'esailing_dateta' => $request->input('sailing_date'),
            'docs_cut_off' => $request->input('docs_cut_off'),
            'original_title_cut_off' => $request->input('original_title_cut_off'),
            'earliest_recievable_date' => $request->input('earliest_recievable_date'),
            'ramp_cut_off' => $request->input('ramp_cut_off'),
            'port_cut_off' => $request->input('port_cut_off'),
        ]);
         if ($quote->shipment && $quote->shipment->customer) {
            //$customerEmail = $quote->shipment->customer->user->email;
            $user = $quote->shipment->customer->user;
            //dd($user->email);
            Mail::to($user->email)->send(new CustomerQuoteMail($quote));
            $user->notify(new customerQuoteNotification($quote));
        }
        return response()->json(['quote' => $quote], 200);
    }

   public function customerDecideQuote(Request $request, $id)
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        DB::beginTransaction();

        try {
            $shipment->update([
                'quote_accepted_status' => $request->input('quote_accepted_status'),
            ]);

            $user = auth()->user()->branch->user;

            $status = $shipment->quote_accepted_status == '1' 
                ? 'Quote Accepted' 
                : 'Quote Rejected';
            //dd($user);
            DB::commit();

            $user->notify(new BookingStatusUpdateNotification($shipment, $status));

            return response()->json(['shipment' => $shipment], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

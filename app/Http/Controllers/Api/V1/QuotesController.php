<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Quote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        $quote = Quote::find($id);
        if (!$quote) {
            return response()->json(['message' => 'Quote not found'], 404);
        }
        $quote->update([
            'user_id' => auth()->user()->id,
            'quoted_amount' => $request->input('quoted_amount'),
            'eta' => $request->input('eta'),
            'esailing_date' => $request->input('sailing_dateta'),
            'docs_cut_off' => $request->input('docs_cut_off'),
            'original_title_cut_off' => $request->input('original_title_cut_off'),
            'earliest_recievable_date' => $request->input('earliest_recievable_date'),
            'ramp_cut_off' => $request->input('ramp_cut_off'),
            'port_cut_off' => $request->input('port_cut_off'),
        ]);
        return response()->json(['quote' => $quote], 200);
    }
}

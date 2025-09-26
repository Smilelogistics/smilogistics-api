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
        $quote->update($request->all());
        return response()->json(['quote' => $quote], 200);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Credit;
use App\Models\CreditPurchase;
use App\Models\Timesheet;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ── Guard: ensure credit record exists before proceeding
        $credit = Credit::firstOrCreate(
            ['user_id' => $user->id],
            ['credit_balance' => 0, 'dollar_cost_per_credit' => null]
        );

        // ── Guard: reject if admin has not yet set a credit rate
        $isLocked = is_null($credit->dollar_cost_per_credit);

        // ── History: determine if this is the customer's first purchase
        $hasPurchased = CreditPurchase::where('user_id', $user->id)->exists();

        // ── Packs: first purchase unlocks 1 credit; repeat buyers get standard packs
        $availablePacks = $hasPurchased ? [4, 6, 8, 10] : [1];

        $history = Timesheet::where('parent_id', $user->id)
            ->with('student')
            ->latest()
            ->get();

        return view('credits.index', compact('credit', 'isLocked', 'availablePacks', 'history'));
    }
}

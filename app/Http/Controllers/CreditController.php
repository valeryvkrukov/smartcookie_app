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

        // to be sure that record already presented in th `credits`
        $credit = Credit::firstOrCreate(
            ['user_id' => $user->id],
            ['credit_balance' => 0, 'dollar_cost_per_credit' => null]
        );

        // check for cost setted by admin
        $isLocked = is_null($credit->dollar_cost_per_credit);

        // check for previous payments
        $hasPurchased = CreditPurchase::where('user_id', $user->id)->exists();

        // ?? check for available packages
        $availablePacks = $hasPurchased ? [4, 6, 8, 10] : [1];

        $history = Timesheet::where('parent_id', $user->id)
            ->with('student')
            ->latest()
            ->get();

        return view('credits.index', compact('credit', 'isLocked', 'availablePacks', 'history'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Credit;
use App\Models\TutoringSession;
use App\Models\CreditPurchase;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'all');
        $query = CreditPurchase::with('user');

        if ($period === 'month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($period === 'quarter') {
            $query->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()]);
        } elseif ($period === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        // Key financial stats
        $stats = [
            'total_revenue' => (clone $query)->sum('total_paid'),
            'tutor_payouts' => TutoringSession::where('status', 'completed')->sum('tutor_rate'),
            'client_balances' => Credit::sum('credit_balance'),
        ];

        // Net profit (Revenue - Payouts)
        $stats['net_profit'] = $stats['total_revenue'] - $stats['tutor_payouts'];

        // Transactions list with filter and pagination
        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.financials.index', compact('stats', 'transactions', 'period'));
    }
}

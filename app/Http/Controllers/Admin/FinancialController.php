<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Credit;
use App\Models\Timesheet;
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

        // ── Stats: compute key financial KPIs
        $timesheetQuery = Timesheet::query();
        if ($period === 'month') {
            $timesheetQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($period === 'quarter') {
            $timesheetQuery->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()]);
        } elseif ($period === 'year') {
            $timesheetQuery->whereYear('created_at', now()->year);
        }

        $tutorPayouts  = (clone $timesheetQuery)->sum('tutor_payout');
        $totalRevenue  = (clone $query)->sum('total_paid')
            ?: DB::table('timesheets as t')
                ->join('credits as c', 'c.user_id', '=', 't.billed_user_id')
                ->when($period === 'month',   fn($q) => $q->whereMonth('t.created_at', now()->month)->whereYear('t.created_at', now()->year))
                ->when($period === 'quarter', fn($q) => $q->whereBetween('t.created_at', [now()->startOfQuarter(), now()->endOfQuarter()]))
                ->when($period === 'year',    fn($q) => $q->whereYear('t.created_at', now()->year))
                ->sum(DB::raw('t.credits_spent * c.dollar_cost_per_credit'));

        $stats = [
            'total_revenue'  => $totalRevenue,
            'tutor_payouts'  => $tutorPayouts,
            'client_balances' => Credit::sum('credit_balance'),
        ];

        // ── Net profit: revenue minus tutor payouts
        $stats['net_profit'] = $stats['total_revenue'] - $stats['tutor_payouts'];

        // ── Search: filter transactions by client name or email
        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // ── Transactions: filtered and paginated payment history
        $transactions = $query->orderBy('created_at', 'desc')->paginate(config('app.pagination_num', 12))->withQueryString();

        return view('admin.financials.index', compact('stats', 'transactions', 'period'));
    }
}

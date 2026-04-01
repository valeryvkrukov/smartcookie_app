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

        /*$clientQuery = User::where('role', 'customer')->with('credit');

        if ($s = $request->input('search_client')) {
            $clientQuery->where(fn($q) => $q->where('first_name', 'like', "%$s%")
                ->orWhere('last_name', 'like', "%$s%"));
        }

        // Client rates
        $clientRates = $clientQuery->orderBy('last_name')->paginate(10, ['*'], 'clients_page');
        
        $tutorQuery = DB::table('tutor_student_assignments')
            ->join('users as tutors', 'tutor_student_assignments.tutor_id', '=', 'tutors.id')
            ->join('users as students', 'tutor_student_assignments.student_id', '=', 'students.id')
            ->select(
                'tutors.first_name as tutor_fname', 'tutors.last_name as tutor_lname',
                'students.first_name as student_fname', 'students.last_name as student_lname',
                'tutor_student_assignments.hourly_payout', 'tutor_student_assignments.id'
            );

        if ($s = $request->input('search_tutor')) {
            $tutorQuery->where(fn($q) => $q->where('tutors.last_name', 'like', "%$s%")
                ->orWhere('students.last_name', 'like', "%$s%"));
        }

        $tutorPayouts = $tutorQuery->orderBy('tutors.last_name')->paginate(10, ['*'], 'tutors_page');

        return view('admin.financials.index', compact('clientRates', 'tutorPayouts'));*/
    }
}

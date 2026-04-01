<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Credit;
use App\Models\TutoringSession;
use App\Models\CreditTransaction;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        // Key financial stats
        $stats = [
            'total_revenue' => CreditTransaction::where('type', 'deposit')->sum('amount'),
            'tutor_payouts' => TutoringSession::where('status', 'completed')->sum('tutor_rate'),
            'client_balances' => Credit::sum('credit_balance'),
        ];

        // Transactions list with filter and pagination
        $query = CreditTransaction::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%");
            });
        }

        return view('admin.financials.index', compact('stats', 'transactions'));

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

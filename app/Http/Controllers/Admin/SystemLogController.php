<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class SystemLogController extends Controller
{
    /** Map short filter key → partial class name */
    private const TYPE_MAP = [
        'registration'      => 'NewClientRegistered',
        'welcome'           => 'WelcomeCustomerRegistered',
        'session_new'       => 'SessionScheduled',
        'session_update'    => 'SessionUpdated',
        'session_completed' => 'SessionCompleted',
        'payment'           => 'CreditBalanceChanged',
        'manual_payment'    => 'ManualPaymentConfirmed',
    ];

    public function index(Request $request)
    {
        $typeFilter  = $request->input('type', 'all');
        $readFilter  = $request->input('read', 'unread'); // 'unread' | 'read' | 'all'
        $search      = trim($request->input('search', ''));

        $query = DatabaseNotification::with('notifiable')
            ->orderBy('created_at', 'desc');

        if ($typeFilter !== 'all' && isset(self::TYPE_MAP[$typeFilter])) {
            $query->where('type', 'like', '%' . self::TYPE_MAP[$typeFilter] . '%');
        }

        if ($readFilter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($readFilter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('data', 'like', $term)
                  ->orWhereHasMorph('notifiable', '*', function ($uq) use ($term) {
                      $uq->where('first_name', 'like', $term)
                         ->orWhere('last_name', 'like', $term)
                         ->orWhere('email', 'like', $term);
                  });
            });
        }

        $logs = $query->paginate(config('app.pagination_num', 12))->withQueryString();

        // ── Payload: data already cast to array by DatabaseNotification
        $logs->each(function ($n) {
            $n->payload = is_array($n->data) ? $n->data : (json_decode($n->data, true) ?? []);
            $n->label   = $this->label($n->type);
            $n->colour  = $this->colour($n->type);
            $n->icon    = $this->icon($n->type);
        });

        // ── Counts: unread per tab
        $counts = [
            'all'               => DatabaseNotification::whereNull('read_at')->count(),
            'registration'      => DatabaseNotification::whereNull('read_at')->where('type', 'like', '%NewClientRegistered%')->count(),
            'welcome'           => DatabaseNotification::whereNull('read_at')->where('type', 'like', '%WelcomeCustomerRegistered%')->count(),
            'session_new'       => DatabaseNotification::whereNull('read_at')->where('type', 'like', '%SessionScheduled%')->count(),
            'session_update'    => DatabaseNotification::whereNull('read_at')->where('type', 'like', '%SessionUpdated%')->count(),
            'session_completed' => DatabaseNotification::whereNull('read_at')->where('type', 'like', '%SessionCompleted%')->count(),
            'payment'           => DatabaseNotification::whereNull('read_at')->where('type', 'like', '%CreditBalanceChanged%')->count(),
            'manual_payment'    => DatabaseNotification::whereNull('read_at')->where('type', 'like', '%ManualPaymentConfirmed%')->count(),
        ];

        return view('admin.system-logs.index', compact('logs', 'typeFilter', 'counts', 'readFilter', 'search'));
    }

    public function markRead(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            DatabaseNotification::whereIn('id', $ids)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }
        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request)
    {
        $typeFilter = $request->input('type', 'all');
        $search     = trim($request->input('search', ''));

        $query = DatabaseNotification::whereNull('read_at');

        if ($typeFilter !== 'all' && isset(self::TYPE_MAP[$typeFilter])) {
            $query->where('type', 'like', '%' . self::TYPE_MAP[$typeFilter] . '%');
        }

        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('data', 'like', $term)
                  ->orWhereHasMorph('notifiable', '*', function ($uq) use ($term) {
                      $uq->where('first_name', 'like', $term)
                         ->orWhere('last_name', 'like', $term)
                         ->orWhere('email', 'like', $term);
                  });
            });
        }

        $count = $query->update(['read_at' => now()]);

        return response()->json(['success' => true, 'count' => $count]);
    }

    private function label(string $class): string
    {
        return match (true) {
            str_contains($class, 'NewClientRegistered')       => 'New Registration',
            str_contains($class, 'WelcomeCustomerRegistered') => 'Welcome Sent',
            str_contains($class, 'SessionScheduled')          => 'Session Scheduled',
            str_contains($class, 'SessionUpdated')            => 'Session Updated',
            str_contains($class, 'SessionCompleted')          => 'Session Completed',
            str_contains($class, 'CreditBalanceChanged')      => 'Balance Changed',
            str_contains($class, 'ManualPaymentConfirmed')        => 'Manual Payment',
            default                                           => class_basename($class),
        };
    }

    private function colour(string $class): string
    {
        return match (true) {
            str_contains($class, 'NewClientRegistered')       => 'indigo',
            str_contains($class, 'WelcomeCustomerRegistered') => 'violet',
            str_contains($class, 'SessionScheduled')          => 'emerald',
            str_contains($class, 'SessionUpdated')            => 'amber',
            str_contains($class, 'SessionCompleted')          => 'teal',
            str_contains($class, 'CreditBalanceChanged')      => 'sky',
            str_contains($class, 'ManualPaymentConfirmed')        => 'emerald',
            default                                           => 'slate',
        };
    }

    private function icon(string $class): string
    {
        return match (true) {
            str_contains($class, 'NewClientRegistered')       => 'ti-user',
            str_contains($class, 'WelcomeCustomerRegistered') => 'ti-email',
            str_contains($class, 'SessionScheduled')          => 'ti-calendar',
            str_contains($class, 'SessionUpdated')            => 'ti-pencil',
            str_contains($class, 'SessionCompleted')          => 'ti-check',
            str_contains($class, 'CreditBalanceChanged')      => 'ti-wallet',
            str_contains($class, 'ManualPaymentConfirmed')        => 'ti-receipt',
            default                                           => 'ti-bell',
        };
    }
}

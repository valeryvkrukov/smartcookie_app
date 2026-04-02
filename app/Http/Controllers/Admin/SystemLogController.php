<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class SystemLogController extends Controller
{
    /** Map short filter key → partial class name */
    private const TYPE_MAP = [
        'registration'  => 'NewClientRegistered',
        'welcome'       => 'WelcomeCustomerRegistered',
        'session_new'   => 'SessionScheduled',
        'session_update'=> 'SessionUpdated',
        'payment'       => 'CreditBalanceChanged',
    ];

    public function index(Request $request)
    {
        $typeFilter = $request->input('type', 'all');

        $query = DatabaseNotification::with('notifiable')
            ->orderBy('created_at', 'desc');

        if ($typeFilter !== 'all' && isset(self::TYPE_MAP[$typeFilter])) {
            $query->where('type', 'like', '%' . self::TYPE_MAP[$typeFilter] . '%');
        }

        $logs = $query->paginate(50)->withQueryString();

        // Decode JSON data and attach a human-readable label + colour class
        $logs->each(function ($n) {
            $n->payload  = json_decode($n->data, true) ?? [];
            $n->label    = $this->label($n->type);
            $n->colour   = $this->colour($n->type);
            $n->icon     = $this->icon($n->type);
        });

        $counts = [
            'all'            => DatabaseNotification::count(),
            'registration'   => DatabaseNotification::where('type', 'like', '%NewClientRegistered%')->count(),
            'welcome'        => DatabaseNotification::where('type', 'like', '%WelcomeCustomerRegistered%')->count(),
            'session_new'    => DatabaseNotification::where('type', 'like', '%SessionScheduled%')->count(),
            'session_update' => DatabaseNotification::where('type', 'like', '%SessionUpdated%')->count(),
            'payment'        => DatabaseNotification::where('type', 'like', '%CreditBalanceChanged%')->count(),
        ];

        return view('admin.system-logs.index', compact('logs', 'typeFilter', 'counts'));
    }

    private function label(string $class): string
    {
        return match (true) {
            str_contains($class, 'NewClientRegistered')      => 'New Registration',
            str_contains($class, 'WelcomeCustomerRegistered') => 'Welcome Sent',
            str_contains($class, 'SessionScheduled')         => 'Session Scheduled',
            str_contains($class, 'SessionUpdated')           => 'Session Updated',
            str_contains($class, 'CreditBalanceChanged')     => 'Balance Changed',
            default                                          => class_basename($class),
        };
    }

    private function colour(string $class): string
    {
        return match (true) {
            str_contains($class, 'NewClientRegistered')      => 'indigo',
            str_contains($class, 'WelcomeCustomerRegistered') => 'violet',
            str_contains($class, 'SessionScheduled')         => 'emerald',
            str_contains($class, 'SessionUpdated')           => 'amber',
            str_contains($class, 'CreditBalanceChanged')     => 'sky',
            default                                          => 'slate',
        };
    }

    private function icon(string $class): string
    {
        return match (true) {
            str_contains($class, 'NewClientRegistered')      => 'ti-user-add',
            str_contains($class, 'WelcomeCustomerRegistered') => 'ti-mail',
            str_contains($class, 'SessionScheduled')         => 'ti-calendar',
            str_contains($class, 'SessionUpdated')           => 'ti-pencil',
            str_contains($class, 'CreditBalanceChanged')     => 'ti-wallet',
            default                                          => 'ti-bell',
        };
    }
}

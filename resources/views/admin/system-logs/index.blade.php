<x-app-layout>
    <x-slot name="header_title">System Logs</x-slot>

    <div class="max-w-7xl mx-auto pb-20 space-y-10">

        {{-- ── FILTER TABS ──────────────────────────────────────────────── --}}
        <div class="flex flex-wrap gap-2">
            @php
                $tabs = [
                    'all'               => ['label' => 'All Events',          'colour' => 'slate'],
                    'registration'      => ['label' => 'Registrations',       'colour' => 'indigo'],
                    'welcome'           => ['label' => 'Welcome Emails',      'colour' => 'violet'],
                    'session_new'       => ['label' => 'Sessions Scheduled',  'colour' => 'emerald'],
                    'session_update'    => ['label' => 'Sessions Updated',    'colour' => 'amber'],
                    'session_completed' => ['label' => 'Session Reports',     'colour' => 'teal'],
                    'payment'           => ['label' => 'Payments',            'colour' => 'sky'],
                ];
            @endphp

            @foreach ($tabs as $key => $tab)
                @php $active = $typeFilter === $key; @endphp
                <a href="{{ route('admin.system-logs.index', ['type' => $key]) }}"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all
                          {{ $active ? 'bg-[#212120] text-white shadow-lg' : 'bg-white text-slate-400 border border-slate-100 hover:text-slate-700 hover:border-slate-200' }}">
                    {{ $tab['label'] }}
                    <span class="px-2 py-0.5 rounded-lg text-[9px] {{ $active ? 'bg-white/20' : 'bg-slate-100' }}">
                        {{ $counts[$key] }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- ── EVENT FEED ───────────────────────────────────────────────── --}}
        @if ($logs->isEmpty())
            <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl p-20 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-[1.5rem] flex items-center justify-center mx-auto mb-6">
                    <i class="ti-info-alt text-2xl text-slate-300"></i>
                </div>
                <h3 class="text-sm font-black text-slate-300 uppercase tracking-widest">No events yet</h3>
                <p class="text-[10px] text-slate-300 mt-2">System events will appear here as they occur.</p>
            </div>
        @else
        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl overflow-hidden">

            {{-- Table header --}}
            <div class="grid grid-cols-12 gap-4 px-10 py-5 bg-slate-50/60 border-b border-slate-100">
                <div class="col-span-3 text-[9px] font-black uppercase tracking-widest text-slate-400">Event</div>
                <div class="col-span-3 text-[9px] font-black uppercase tracking-widest text-slate-400">Recipient</div>
                <div class="col-span-4 text-[9px] font-black uppercase tracking-widest text-slate-400">Details</div>
                <div class="col-span-1 text-[9px] font-black uppercase tracking-widest text-slate-400">Read</div>
                <div class="col-span-1 text-[9px] font-black uppercase tracking-widest text-slate-400 text-right">Time</div>
            </div>

            {{-- Rows --}}
            <div class="divide-y divide-slate-50">
                @foreach ($logs as $log)
                @php
                    $p       = $log->payload;
                    $colour  = $log->colour;
                    $palettes = [
                        'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-600',  'dot' => 'bg-indigo-400'],
                        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600',  'dot' => 'bg-violet-400'],
                        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'dot' => 'bg-emerald-400'],
                        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600',   'dot' => 'bg-amber-400'],
                        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-600',    'dot' => 'bg-teal-400'],
                        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600',     'dot' => 'bg-sky-400'],
                        'slate'   => ['bg' => 'bg-slate-50',   'text' => 'text-slate-600',   'dot' => 'bg-slate-400'],
                    ];
                    $pal = $palettes[$colour] ?? $palettes['slate'];
                @endphp
                <div class="grid grid-cols-12 gap-4 px-10 py-6 hover:bg-slate-50/40 transition-colors items-start">

                    {{-- Event type badge --}}
                    <div class="col-span-3 flex items-center gap-3">
                        <div class="w-9 h-9 {{ $pal['bg'] }} {{ $pal['text'] }} rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="{{ $log->icon }} text-sm"></i>
                        </div>
                        <span class="px-3 py-1 {{ $pal['bg'] }} {{ $pal['text'] }} rounded-lg text-[9px] font-black uppercase tracking-widest">
                            {{ $log->label }}
                        </span>
                    </div>

                    {{-- Recipient --}}
                    <div class="col-span-3">
                        @if ($log->notifiable)
                            <p class="text-sm font-black text-slate-900 leading-tight">{{ $log->notifiable->full_name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tight truncate">{{ $log->notifiable->email }}</p>
                            <span class="mt-1 inline-block px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-black uppercase tracking-widest">
                                {{ $log->notifiable->role ?? '—' }}
                            </span>
                        @else
                            <span class="text-[10px] text-slate-300 italic">deleted user</span>
                        @endif
                    </div>

                    {{-- Payload details --}}
                    <div class="col-span-4 space-y-1">
                        @if (!empty($p['message']))
                            <p class="text-xs font-bold text-slate-700">{{ $p['message'] }}</p>
                        @endif

                        @if (!empty($p['parent_name']))
                            <p class="text-[10px] text-slate-500">Parent: <span class="font-bold text-slate-700">{{ $p['parent_name'] }}</span></p>
                        @endif
                        @if (!empty($p['student_name']))
                            <p class="text-[10px] text-slate-500">Student: <span class="font-bold text-slate-700">{{ $p['student_name'] }}</span></p>
                        @endif
                        @if (!empty($p['subject']))
                            <p class="text-[10px] text-slate-500">Subject: <span class="font-bold text-slate-700">{{ $p['subject'] }}</span></p>
                        @endif
                        @if (!empty($p['date']))
                            <p class="text-[10px] text-slate-500">Date: <span class="font-bold text-slate-700">{{ \Carbon\Carbon::parse($p['date'])->format('M d, Y') }}</span></p>
                        @endif
                        @if (!empty($p['tutor_name']))
                            <p class="text-[10px] text-slate-500">Tutor: <span class="font-bold text-slate-700">{{ $p['tutor_name'] }}</span></p>
                        @endif
                        @if (!empty($p['tutor_notes']))
                            <p class="text-[10px] text-slate-500 mt-1">Report: <span class="font-medium text-slate-600 italic">{{ Str::limit($p['tutor_notes'], 120) }}</span></p>
                        @endif
                        @if (isset($p['amount']))
                            @php
                                $sign = ($p['direction'] ?? 'credit') === 'credit' ? '+' : '-';
                                $signClass = $sign === '+' ? 'text-emerald-600' : 'text-rose-600';
                            @endphp
                            <p class="text-[10px] text-slate-500">
                                Amount: <span class="font-black {{ $signClass }}">{{ $sign }}{{ number_format($p['amount'], 2) }}</span>
                                &nbsp;→ Balance: <span class="font-bold text-slate-700">{{ number_format($p['balance_after'] ?? 0, 2) }}</span>
                            </p>
                            @if (!empty($p['reason']))
                                <p class="text-[9px] text-slate-400 italic">{{ $p['reason'] }}</p>
                            @endif
                        @endif
                    </div>

                    {{-- Read status --}}
                    <div class="col-span-1 flex items-center pt-1">
                        @if ($log->read_at)
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                                <span class="text-[8px] font-black uppercase tracking-widest text-emerald-600">Read</span>
                            </div>
                        @else
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-400"></div>
                                <span class="text-[8px] font-black uppercase tracking-widest text-amber-600">Unread</span>
                            </div>
                        @endif
                    </div>

                    {{-- Timestamp --}}
                    <div class="col-span-1 text-right pt-1">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $log->created_at->format('M d') }}</p>
                        <p class="text-[10px] font-bold text-slate-300">{{ $log->created_at->format('H:i') }}</p>
                        <p class="text-[8px] text-slate-200 mt-0.5" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</p>
                    </div>

                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="px-10 py-6 bg-slate-50/40 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        </div>
        @endif

    </div>
</x-app-layout>

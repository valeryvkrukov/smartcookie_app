<x-app-layout>
    <x-slot name="header_title">System Logs</x-slot>

    @php
        $allIds    = $logs->pluck('id')->toArray();
        $allIdsJson = json_encode($allIds);
        $tabs = [
            'all'               => ['label' => 'All Events',         'colour' => 'slate'],
            'registration'      => ['label' => 'Registrations',      'colour' => 'indigo'],
            'welcome'           => ['label' => 'Welcome Emails',     'colour' => 'violet'],
            'session_new'       => ['label' => 'Sessions Scheduled', 'colour' => 'emerald'],
            'session_update'    => ['label' => 'Sessions Updated',   'colour' => 'amber'],
            'session_completed' => ['label' => 'Session Reports',    'colour' => 'teal'],
            'payment'           => ['label' => 'Payments',           'colour' => 'sky'],
        ];
    @endphp

    <div class="max-w-7xl mx-auto pb-20 space-y-6"
         x-data="{
             selectedIds: [],
             allSelected() { return this.selectedIds.length === {{ count($allIds) }} && {{ count($allIds) }} > 0; },
             toggleAll() {
                 this.selectedIds = this.allSelected() ? [] : {{ $allIdsJson }};
             },
             async markSelected(ids = null) {
                 const toMark = ids ?? this.selectedIds;
                 if (!toMark.length) return;
                 await fetch('{{ route('admin.system-logs.mark-read') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     },
                     body: JSON.stringify({ ids: toMark }),
                 });
                 window.location.reload();
             },
             async markAll() {
                 await fetch('{{ route('admin.system-logs.mark-all-read') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     },
                     body: JSON.stringify({ type: @json($typeFilter), search: @json($search) }),
                 });
                 window.location.reload();
             },
         }">

        {{-- ── Filter tabs: category buttons to filter event feed --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as $key => $tab)
                @php $active = $typeFilter === $key; @endphp
                <a href="{{ route('admin.system-logs.index', array_filter(['type' => $key, 'search' => $search ?: null, 'read' => $readFilter !== 'unread' ? $readFilter : null])) }}"
                   class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all
                          {{ $active ? 'bg-[#212120] text-white shadow-lg' : 'bg-white text-slate-400 border border-slate-100 hover:text-slate-700 hover:border-slate-200' }}">
                    {{ $tab['label'] }}
                    @if ($counts[$key] > 0)
                        <span class="px-2 py-0.5 rounded-lg text-[9px] {{ $active ? 'bg-white/20' : 'bg-amber-100 text-amber-600' }}">
                            {{ $counts[$key] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- ── Controls: search, unread toggle, mark-all --}}
        <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">

            {{-- Search form --}}
            <form action="{{ route('admin.system-logs.index') }}" method="GET" class="flex flex-1 gap-2">
                <input type="hidden" name="type" value="{{ $typeFilter }}">
                <input type="hidden" name="read"  value="{{ $readFilter }}">
                <div class="relative flex-1">
                    <i class="ti-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-sm pointer-events-none"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search by name, email or event data…"
                           class="w-full pl-10 pr-4 py-3 bg-white border border-slate-100 rounded-2xl text-[11px] font-bold focus:ring-2 focus:ring-[#212120] focus:border-transparent transition-all shadow-sm">
                </div>
                <button type="submit"
                        class="px-6 py-3 bg-[#212120] text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-sm hover:bg-black transition-all">
                    Search
                </button>
                @if ($search)
                    <a href="{{ route('admin.system-logs.index', array_filter(['type' => $typeFilter !== 'all' ? $typeFilter : null, 'read' => $readFilter !== 'unread' ? $readFilter : null])) }}">
                       class="px-4 py-3 bg-white border border-slate-100 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-700 transition-all flex items-center gap-1.5">
                        <i class="ti-close text-xs"></i> Clear
                    </a>
                @endif
            </form>

            {{-- Read filter: 3-way toggle --}}
            @php
                $baseParams = array_filter(['type' => $typeFilter !== 'all' ? $typeFilter : null, 'search' => $search ?: null]);
                $filterOptions = [
                    'unread' => ['label' => 'Unread', 'dot' => 'bg-amber-400', 'active' => 'bg-amber-50 text-amber-600 border border-amber-100'],
                    'read'   => ['label' => 'Read',   'dot' => 'bg-emerald-400', 'active' => 'bg-emerald-50 text-emerald-600 border border-emerald-100'],
                    'all'    => ['label' => 'All',    'dot' => 'bg-slate-300', 'active' => 'bg-slate-100 text-slate-700 border border-slate-200'],
                ];
            @endphp
            <div class="flex rounded-2xl overflow-hidden border border-slate-100 bg-white shadow-sm">
                @foreach ($filterOptions as $value => $opt)
                    @php $isActive = $readFilter === $value; @endphp
                    <a href="{{ route('admin.system-logs.index', array_merge($baseParams, $value !== 'unread' ? ['read' => $value] : [])) }}"
                       class="flex items-center gap-1.5 px-4 py-3 text-[10px] font-black uppercase tracking-widest transition-all
                              {{ $isActive ? $opt['active'] : 'text-slate-400 hover:text-slate-600' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ $opt['dot'] }}"></div>
                        {{ $opt['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- Mark all as read --}}
            @if ($readFilter !== 'read' && $counts['all'] > 0)
                <button @click="markAll()"
                        class="px-5 py-3 bg-white border border-slate-100 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-800 hover:border-slate-200 transition-all shadow-sm flex items-center gap-2">
                    <i class="ti-check text-xs"></i>
                    Mark all as read
                </button>
            @endif
        </div>

        {{-- ── Event feed: paginated log entries --}}
        @if ($logs->isEmpty())
            <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl p-20 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-[1.5rem] flex items-center justify-center mx-auto mb-6">
                    <i class="ti-check text-2xl text-slate-200"></i>
                </div>
                @if ($readFilter === 'unread')
                    <h3 class="text-sm font-black text-slate-300 uppercase tracking-widest">All caught up</h3>
                    <p class="text-[10px] text-slate-300 mt-2">No unread events{{ $search ? ' matching your search' : '' }}.</p>
                    <a href="{{ route('admin.system-logs.index', array_filter(['type' => $typeFilter !== 'all' ? $typeFilter : null, 'search' => $search ?: null, 'read' => 'all'])) }}"
                       class="mt-6 inline-block px-6 py-3 bg-slate-50 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-all">
                        Show all events
                    </a>
                @elseif ($readFilter === 'read')
                    <h3 class="text-sm font-black text-slate-300 uppercase tracking-widest">No read events</h3>
                    <p class="text-[10px] text-slate-300 mt-2">No events have been marked as read yet.</p>
                @else
                    <h3 class="text-sm font-black text-slate-300 uppercase tracking-widest">No events yet</h3>
                    <p class="text-[10px] text-slate-300 mt-2">System events will appear here as they occur.</p>
                @endif
            </div>
        @else
        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl overflow-hidden">

            {{-- Table header --}}
            <div class="grid grid-cols-12 gap-4 px-10 py-5 bg-slate-50/60 border-b border-slate-100 items-center">

                {{-- Select-all checkbox OR bulk action bar --}}
                <div class="col-span-1 flex items-center">
                    <input type="checkbox"
                           :checked="allSelected()"
                           @change="toggleAll()"
                           class="w-4 h-4 rounded border-2 border-slate-200 text-[#212120] focus:ring-0 cursor-pointer transition-all">
                </div>

                {{-- Column labels (hidden when rows are selected) --}}
                <template x-if="selectedIds.length === 0">
                    <div class="col-span-11 grid grid-cols-11 gap-4">
                        <div class="col-span-2 text-[9px] font-black uppercase tracking-widest text-slate-400">Event</div>
                        <div class="col-span-2 text-[9px] font-black uppercase tracking-widest text-slate-400">Recipient</div>
                        <div class="col-span-4 text-[9px] font-black uppercase tracking-widest text-slate-400">Details</div>
                        <div class="col-span-1 text-[9px] font-black uppercase tracking-widest text-slate-400">Status</div>
                        <div class="col-span-2 text-[9px] font-black uppercase tracking-widest text-slate-400 text-right">Time</div>
                    </div>
                </template>

                {{-- Bulk action bar (shown when rows are selected) --}}
                <template x-if="selectedIds.length > 0">
                    <div class="col-span-11 flex items-center gap-4">
                        <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">
                            <span x-text="selectedIds.length"></span> selected
                        </span>
                        <button @click="markSelected()"
                                class="flex items-center gap-1.5 px-4 py-1.5 bg-[#212120] text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-black transition-all">
                            <i class="ti-check text-xs"></i> Mark as read
                        </button>
                        <button @click="selectedIds = []"
                                class="text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">
                            Deselect all
                        </button>
                    </div>
                </template>
            </div>

            {{-- Rows --}}
            <div class="divide-y divide-slate-50">
                @foreach ($logs as $log)
                @php
                    $p        = $log->payload;
                    $colour   = $log->colour;
                    $isUnread = is_null($log->read_at);
                    $palettes = [
                        'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-600'],
                        'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-600'],
                        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
                        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600'],
                        'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-600'],
                        'sky'     => ['bg' => 'bg-sky-50',     'text' => 'text-sky-600'],
                        'slate'   => ['bg' => 'bg-slate-50',   'text' => 'text-slate-600'],
                    ];
                    $pal = $palettes[$colour] ?? $palettes['slate'];
                @endphp
                <div class="grid grid-cols-12 gap-4 px-10 py-6 hover:bg-slate-50/40 transition-colors items-start
                            {{ $readFilter === 'all' && !$isUnread ? 'opacity-50' : '' }}">

                    {{-- Row checkbox --}}
                    <div class="col-span-1 flex items-center pt-0.5">
                        <input type="checkbox"
                               value="{{ $log->id }}"
                               x-model="selectedIds"
                               class="w-4 h-4 rounded border-2 border-slate-200 text-[#212120] focus:ring-0 cursor-pointer transition-all">
                    </div>

                    {{-- Event type badge --}}
                    <div class="col-span-2 flex items-center gap-2">
                        <div class="w-8 h-8 {{ $pal['bg'] }} {{ $pal['text'] }} rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="{{ $log->icon }} text-sm"></i>
                        </div>
                        <span class="px-2 py-1 {{ $pal['bg'] }} {{ $pal['text'] }} rounded-lg text-[8px] font-black uppercase tracking-widest leading-tight">
                            {{ $log->label }}
                        </span>
                    </div>

                    {{-- Recipient --}}
                    <div class="col-span-2">
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
                                $sign      = ($p['direction'] ?? 'credit') === 'credit' ? '+' : '-';
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

                    {{-- Read status + per-row action --}}
                    <div class="col-span-1 flex flex-col items-start gap-2 pt-0.5">
                        @if ($isUnread)
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-400"></div>
                                <span class="text-[8px] font-black uppercase tracking-widest text-amber-600">Unread</span>
                            </div>
                            <button @click="markSelected(['{{ $log->id }}'])"
                                    class="text-[8px] font-black uppercase tracking-widest text-slate-300 hover:text-slate-600 transition-colors leading-none">
                                Mark read
                            </button>
                        @else
                            <div class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-200"></div>
                                <span class="text-[8px] font-black uppercase tracking-widest text-slate-300">Read</span>
                            </div>
                        @endif
                    </div>

                    {{-- Timestamp --}}
                    <div class="col-span-2 text-right pt-0.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $log->created_at->format('M d') }}</p>
                        <p class="text-[10px] font-bold text-slate-300">{{ $log->created_at->format('H:i') }}</p>
                        <p class="text-[8px] text-slate-200 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
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

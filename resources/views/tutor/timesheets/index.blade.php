<x-app-layout>
    <x-slot name="header_title">My Timesheets</x-slot>

    <div class="max-w-6xl mx-auto space-y-10">
        
        <!-- ALERT: Pending Logs (Modern Design) -->
        @if($pendingSessions->count() > 0)
        <div class="bg-rose-50 border border-rose-100 rounded-[2.5rem] p-8 flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <div class="w-12 h-12 bg-rose-500 text-white rounded-2xl flex items-center justify-center animate-pulse">
                    <i class="ti-alert text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-rose-900 uppercase tracking-tight">Attention Required</h3>
                    <p class="text-[10px] font-bold text-rose-400 uppercase tracking-widest mt-1">
                        You have {{ $pendingSessions->count() }} sessions waiting for your report.
                    </p>
                </div>
            </div>
            <button class="px-6 py-3 bg-rose-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg shadow-rose-200">
                Fix Now
            </button>
        </div>
        @endif

        <!-- SESSIONS (Glass Style) -->
        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50/50 border-b border-slate-50">
                    <tr>
                        <th class="p-6 text-left text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Date & Student</th>
                        <th class="p-6 text-left text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Subject</th>
                        <th class="p-6 text-left text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Status</th>
                        <th class="p-6 text-right text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($sessions as $s)
                    <tr class="group hover:bg-slate-50/50 transition-all">
                        <td class="p-6">
                            <p class="font-black text-slate-900 text-sm tracking-tight">{{ $s->date->format('M d, Y') }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ $s->student->full_name }}</p>
                        </td>
                        <td class="p-6">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase rounded-lg">{{ $s->subject }}</span>
                        </td>
                        <td class="p-6">
                            @if($s->status === 'Completed')
                                <span class="text-emerald-500 font-black uppercase text-[9px] font-black uppercase tracking-widest">
                                    <i class="ti-check mr-2"></i> Completed
                                </span>
                            @elseif($s->date->isPast())
                                <span class="text-rose-500 font-black uppercase text-[9px] animate-pulse font-black uppercase tracking-widest">
                                    <i class="ti-check mr-2"></i> Pending Log
                                </span>
                            @else
                                <span class="text-slate-400 font-black uppercase text-[9px] font-black uppercase tracking-widest">
                                    <i class="ti-check mr-2"></i> Upcoming
                                </span>
                            @endif
                        </td>
                        <td class="p-6 text-right">
                            <button @click="$dispatch('open-modal', { type: 'session-log', sessionId: '{{ $s->id }}' })" 
                                    class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-colors">
                                {{ $s->status === 'Completed' ? 'Edit Log' : 'Write Report' }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

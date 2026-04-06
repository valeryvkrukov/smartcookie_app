<x-app-layout>
    <x-slot name="header_title">Financial Intelligence</x-slot>

    <div class="max-w-7xl mx-auto space-y-10 pb-20">
        
        {{-- ── Period selector: pill-style filter for time range --}}
        <div class="flex justify-center">
            <div class="inline-flex bg-white p-1.5 rounded-[2rem] border border-slate-100 shadow-xl overflow-x-auto max-w-full">
                @foreach(['all' => 'All Time', 'month' => 'This Month', 'quarter' => 'Quarter', 'year' => 'Year'] as $key => $label)
                    <a href="{{ route('admin.financials.index', ['period' => $key]) }}" 
                       class="px-8 py-3 rounded-full text-[10px] font-black uppercase tracking-widest transition-all {{ $period === $key ? 'bg-[#212120] text-white shadow-lg' : 'text-slate-400 hover:text-slate-600' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ── Stats grid: four KPI cards (profit, revenue, payouts, credits) --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            {{-- ── Net profit: primary KPI card --}}
            <div class="bg-[#1A1A19] rounded-[3.5rem] p-8 text-white shadow-2xl relative overflow-hidden group border border-white/5">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/20 blur-[60px] rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <p class="text-[9px] font-black uppercase tracking-[0.4em] text-emerald-400 mb-4">Net Profit</p>
                <h2 class="text-4xl font-black tracking-tighter">${{ number_format($stats['net_profit'], 2) }}</h2>
            </div>

            {{-- ── Gross revenue: total payments received --}}
            <div class="bg-white rounded-[3.5rem] p-8 border border-slate-100 shadow-xl">
                <p class="text-[9px] font-black uppercase tracking-[0.4em] text-slate-400 mb-4">Gross Revenue</p>
                <h2 class="text-4xl font-black tracking-tighter text-slate-900">${{ number_format($stats['total_revenue'], 2) }}</h2>
            </div>

            {{-- ── Tutor payouts: total paid out to tutors --}}
            <div class="bg-white rounded-[3.5rem] p-8 border border-slate-100 shadow-xl">
                <p class="text-[9px] font-black uppercase tracking-[0.4em] text-rose-500 mb-4">Tutor Payouts</p>
                <h2 class="text-4xl font-black tracking-tighter text-slate-900">${{ number_format($stats['tutor_payouts'], 2) }}</h2>
            </div>

            {{-- ── Client credits: outstanding credit liabilities --}}
            <div class="bg-white rounded-[3.5rem] p-8 border border-slate-100 shadow-xl">
                <p class="text-[9px] font-black uppercase tracking-[0.4em] text-slate-400 mb-4">Client Credits</p>
                <h2 class="text-4xl font-black tracking-tighter text-slate-900">{{ number_format($stats['client_balances'], 2) }} <span class="text-lg text-slate-400">credits</span></h2>
            </div>
        </div>

        {{-- ── Transaction log: searchable and paginated payment history --}}
        <div class="bg-white rounded-[3.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-6">
                <h3 class="text-xl font-black text-slate-900 tracking-tight">Recent Transactions</h3>
                
                {{-- ── Search: filters transaction log by client name --}}
                <form action="{{ route('admin.financials.index') }}" method="GET" class="relative w-full md:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full pl-6 pr-12 py-4 bg-slate-50 border-none rounded-2xl text-[11px] font-bold focus:ring-2 focus:ring-indigo-500 transition-all" 
                           placeholder="SEARCH CLIENT...">
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"><i class="ti-search"></i></button>
                </form>
            </div>

            <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[560px]">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400">Date & Client</th>
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400">Type</th>
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400 text-right">Credits / USD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($transactions as $t)
                    <tr class="group hover:bg-slate-50/30 transition-all">
                        <td class="p-8">
                            <p class="text-sm font-black text-slate-900">{{ $t->user->full_name }}</p>
                            <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">{{ $t->created_at->format('M d, Y • H:i') }}</p>
                        </td>
                        <td class="p-8">
                            <span class="px-4 py-1.5 {{ $t->type === 'deposit' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} rounded-xl text-[9px] font-black uppercase tracking-widest">
                                {{ $t->type ?? 'Deposit' }}
                            </span>
                        </td>
                        <td class="p-8">
                            <div class="flex items-center space-x-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Verified</span>
                            </div>
                        </td>
                        <td class="p-8 text-right">
                            @if($t->credits_purchased)
                                <p class="font-black text-indigo-600 text-lg tracking-tighter">{{ $t->type === 'deposit' ? '+' : '-' }}{{ number_format($t->credits_purchased, 2) }} <span class="text-sm">credits</span></p>
                            @endif
                            <p class="text-sm font-bold text-slate-400">${{ number_format($t->total_paid, 2) }}</p>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>{{-- end overflow-x-auto --}}
            
            <div class="p-8 bg-slate-50/50">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

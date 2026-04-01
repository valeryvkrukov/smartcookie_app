<x-app-layout>
    <x-slot name="header_title text-slate-900">Financial Intelligence</x-slot>

    <div class="max-w-7xl mx-auto space-y-10 pb-20">
        
        <!-- TOP STATS CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Revenue -->
            <div class="bg-[#1A1A19] rounded-[3.5rem] p-10 text-white shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-600/20 blur-[80px] rounded-full group-hover:scale-125 transition-transform duration-700"></div>
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-white/40 mb-4">Gross Revenue</p>
                <h2 class="text-5xl font-black tracking-tighter">${{ number_format($stats['total_revenue'], 2) }}</h2>
            </div>

            <!-- Payouts -->
            <div class="bg-white rounded-[3.5rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/50">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 mb-4 text-rose-500">Tutor Payouts (Due)</p>
                <h2 class="text-5xl font-black tracking-tighter text-slate-900">${{ number_format($stats['tutor_payouts'], 2) }}</h2>
            </div>

            <!-- Liabilities -->
            <div class="bg-white rounded-[3.5rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/50">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 mb-4 text-emerald-500">Held in Credits</p>
                <h2 class="text-5xl font-black tracking-tighter text-slate-900">${{ number_format($stats['client_balances'], 2) }}</h2>
            </div>
        </div>

        <!-- TRANSACTION LOG -->
        <div class="bg-white rounded-[3.5rem] border border-slate-100 shadow-xl overflow-hidden">
            <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-6">
                <h3 class="text-xl font-black text-slate-900 tracking-tight">Recent Transactions</h3>
                
                <!-- Search -->
                <form action="{{ route('admin.financials.index') }}" method="GET" class="relative w-full md:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full pl-6 pr-12 py-4 bg-slate-50 border-none rounded-2xl text-[11px] font-bold focus:ring-2 focus:ring-indigo-500 transition-all" 
                           placeholder="SEARCH CLIENT...">
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"><i class="ti-search"></i></button>
                </form>
            </div>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400">Date & Client</th>
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400">Type</th>
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="p-8 text-[9px] font-black uppercase tracking-widest text-slate-400 text-right">Amount</th>
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
                                {{ $t->type }}
                            </span>
                        </td>
                        <td class="p-8">
                            <div class="flex items-center space-x-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Verified</span>
                            </div>
                        </td>
                        <td class="p-8 text-right font-black text-slate-900 text-lg tracking-tighter">
                            {{ $t->type === 'deposit' ? '+' : '-' }}${{ number_format($t->total_paid, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="p-8 bg-slate-50/50">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

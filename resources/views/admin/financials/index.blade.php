<x-app-layout>
    <x-slot name="header_title text-slate-900">Financial Center</x-slot>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-10 items-start">
        
        <!-- CLIENTS (Revenue) -->
        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-slate-900 uppercase text-[10px] tracking-[0.2em]">Client Rates</h3>
                    <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest mt-1">Incoming Revenue</p>
                </div>
                <!-- SEARCH (Modern Style) -->
                <form action="{{ route('admin.financials.index') }}" method="GET" class="relative flex items-center">
                    <input type="text" name="search_client" value="{{ request('search_client') }}" 
                           class="w-full md:w-64 pl-5 pr-12 py-3 bg-white border border-slate-100 rounded-2xl text-[11px] font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-sm" placeholder="Search...">
                    <button class="absolute right-4 flex items-center justify-center text-slate-400 hover:text-indigo-600 transition-colors">
                        <i class="ti-search text-sm"></i>
                    </button>
                </form>
            </div>
            
            <table class="w-full">
                <tbody class="divide-y divide-slate-50">
                    @foreach($clientRates as $client)
                    <tr class="group hover:bg-slate-50 transition-colors">
                        <td class="p-6">
                            <p class="font-black text-slate-900 text-sm tracking-tight">{{ $client->full_name }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Active Contract</p>
                        </td>
                        <td class="p-6 text-right">
                            <div class="inline-block px-4 py-2 bg-indigo-50 rounded-2xl">
                                <span class="font-black text-indigo-600 text-sm">${{ number_format($client->credit->dollar_cost_per_credit ?? 0, 2) }}</span>
                                <span class="text-[8px] font-bold text-indigo-400 uppercase ml-1">/ credit</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $clientRates->appends(request()->except('clients_page'))->links() }}
            </div>
        </div>

        <!-- SECTION: TUTOR PAYOUTS -->
        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-rose-50/20 flex justify-between items-center">
                <div>
                    <h3 class="font-black text-slate-900 uppercase text-[10px] tracking-[0.2em]">Tutor Payouts</h3>
                    <p class="text-[9px] font-bold text-rose-500 uppercase tracking-widest mt-1">Operational Expenses</p>
                </div>
                <!-- SEARCH -->
                <form action="{{ route('admin.financials.index') }}" method="GET" class="relative flex items-center">
                    <input type="text" name="search_tutor" value="{{ request('search_tutor') }}" 
                        class="w-full md:w-64 pl-5 pr-12 py-3 bg-white border border-slate-100 rounded-2xl text-[11px] font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-sm" placeholder="Search...">
                    <button type="submit" class="absolute right-4 flex items-center justify-center text-slate-400 hover:text-indigo-600 transition-colors">
                        <i class="ti-search text-sm"></i>
                    </button>
                </form>
            </div>
            
            <table class="w-full">
                <tbody class="divide-y divide-slate-50">
                    @foreach($tutorPayouts as $p)
                    <tr class="group hover:bg-rose-50/30 transition-colors">
                        <td class="p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center text-xs font-black">
                                    {{ substr($p->tutor_lname, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 text-sm tracking-tight">{{ $p->tutor_fname }} {{ $p->tutor_lname }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter italic">Teaching: {{ $p->student_fname }} {{ $p->student_lname }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-right">
                            <div class="inline-block px-4 py-2 bg-rose-50 rounded-2xl border border-rose-100">
                                <span class="font-black text-rose-600 text-sm">${{ number_format($p->hourly_payout, 2) }}</span>
                                <span class="text-[8px] font-bold text-rose-400 uppercase ml-1">/ hour</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $tutorPayouts->appends(request()->except('tutors_page'))->links() }}
            </div>
        </div>

    </div>
</x-app-layout>

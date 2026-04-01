<x-app-layout>
    <x-slot name="header_title">Compliance Control</x-slot>

    <!-- Filters and Search in "Glass" -->
    <div class="mb-10 p-6 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex flex-wrap gap-4 items-center justify-between">
        <form action="{{ route('admin.agreements.index') }}" method="GET" class="flex flex-1 max-w-md relative group">
            <input type="hidden" name="status" value="{{ request('status', 'Signed') }}">
            <input type="text" name="search" value="{{ request('search') }}" 
                   class="w-full pl-5 pr-12 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all" 
                   placeholder="Search client or document...">
            <button class="absolute right-4 top-4 text-slate-400 group-hover:text-indigo-600 transition-colors">
                <i class="ti-search"></i>
            </button>
        </form>

        <div class="flex bg-slate-50 p-1.5 rounded-2xl border border-slate-100">
            @foreach(['Signed' => 'emerald', 'Pending' => 'amber'] as $status => $color)
                <a href="{{ route('admin.agreements.index', array_filter(['status' => $status, 'search' => request('search')])) }}" 
                   class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ request('status', 'Signed') == $status ? 'bg-white text-'.$color.'-600 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                    {{ $status }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Request Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        @foreach($requests as $req)
        <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden flex flex-col group hover:-translate-y-1 transition-all duration-500">
            <div class="p-8 flex-1">
                <div class="flex justify-between items-start mb-6">
                    <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                        <i class="ti-write text-xl"></i>
                    </div>
                    @if($req->status === 'Signed')
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-lg">Verified</span>
                    @else
                        <span class="px-3 py-1 bg-amber-50 text-amber-600 text-[9px] font-black uppercase rounded-lg animate-pulse">Awaiting</span>
                    @endif
                </div>

                <h3 class="text-lg font-black text-slate-900 tracking-tight leading-tight mb-2">{{ $req->user->full_name }}</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">{{ $req->agreement->name }}</p>
                
                <div class="space-y-3 pt-6 border-t border-slate-50">
                    <div class="flex justify-between text-[10px] font-bold">
                        <span class="text-slate-400 uppercase">Signed On:</span>
                        <span class="text-slate-900">{{ $req->signed_at ? $req->signed_at->format('M d, Y') : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-[10px] font-bold">
                        <span class="text-slate-400 uppercase">IP Address:</span>
                        <span class="text-slate-500 font-mono">{{ $req->ip_address ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            
            <a href="{{ asset('storage/'.$req->agreement->pdf_path) }}" target="_blank" 
               class="p-5 bg-slate-50 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:bg-[#212120] hover:text-white transition-all">
                View Document PDF
            </a>
        </div>
        @endforeach
    </div>
</x-app-layout>

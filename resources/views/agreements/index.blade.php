<x-app-layout>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
        @foreach($agreements as $agreement)
        <div class="relative bg-white p-10 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/40">
            @php $isSigned = in_array($agreement->id, $signedRequests); @endphp
            
            <div class="mb-8 flex items-center justify-between">
                <div class="w-16 h-16 {{ $isSigned ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} rounded-[1.5rem] flex items-center justify-center text-3xl">
                    <i class="ti-write"></i>
                </div>
                @if($isSigned)
                    <div class="flex items-center text-emerald-500 font-black text-[10px] uppercase tracking-widest">
                        <i class="ti-check-box mr-2"></i> Completed
                    </div>
                @endif
            </div>

            <h3 class="text-2xl font-black text-slate-900 tracking-tight mb-4">{{ $agreement->name }}</h3>
            <p class="text-sm text-slate-500 leading-relaxed mb-10">{{ $agreement->content ?? 'Please review and provide your electronic signature for this document.' }}</p>

            @if($isSigned)
                <button disabled class="w-full py-5 bg-slate-50 text-slate-400 rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] cursor-not-allowed">
                    Document Signed
                </button>
            @else
                <a href="{{ route('agreements.show', $agreement->id) }}" class="btn-primary flex items-center justify-center">
                    Review & Sign Contract
                </a>
            @endif
        </div>
        @endforeach
    </div>

</x-app-layout>
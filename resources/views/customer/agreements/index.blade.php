<x-app-layout>
    <x-slot name="header_title">Policies & Compliance</x-slot>

    <div class="max-w-4xl mx-auto space-y-6 pb-20">
        
        <!-- Section header -->
        <div class="mb-10">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Required Documents</h2>
            @if($requests->count() > 0)
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2 text-balance">
                Please review and sign the following documents to maintain full access to your account.
            </p>
            @endif
        </div>

        @forelse($requests as $req)
            <div class="relative group bg-white p-10 rounded-[3rem] border {{ $req->status === 'Signed' ? 'border-slate-100 shadow-xl' : 'border-rose-100 shadow-2xl shadow-rose-200/30' }} transition-all duration-500">
                
                <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="flex items-center space-x-8">
                        <!-- Status Icon -->
                        <div class="w-16 h-16 {{ $req->status === 'Signed' ? 'bg-slate-50 text-slate-300' : 'bg-rose-50 text-rose-500' }} rounded-[1.5rem] flex items-center justify-center text-3xl shadow-inner">
                            <i class="{{ $req->status === 'Signed' ? 'ti-check-box' : 'ti-file' }}"></i>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $req->agreement->name }}</h3>
                            <div class="flex items-center mt-1 space-x-3">
                                <span class="text-[9px] font-black uppercase tracking-widest {{ $req->status === 'Signed' ? 'text-emerald-500' : 'text-rose-400' }}">
                                    {{ $req->status }}
                                </span>
                                @if($req->signed_at)
                                    <span class="text-[9px] font-bold text-slate-300 uppercase tracking-tighter italic">
                                        Signed on {{ $req->signed_at->format('M d, Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- View PDF (from migration pdf_path) -->
                        <a href="{{ asset('storage/' . $req->agreement->pdf_path) }}" target="_blank" 
                           class="px-6 py-4 bg-slate-50 text-slate-400 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-100 transition-all active:scale-95">
                            View PDF
                        </a>

                        @if($req->status !== 'Signed')
                            <button type="button"
                                    onclick="window.dispatchEvent(new CustomEvent('open-modal', { 
                                        detail: { 
                                            type: 'sign-agreement', 
                                            requestId: '{{ $req->id }}', 
                                            title: 'Sign {{ $req->agreement->name }}' 
                                        } 
                                    }))"
                                    class="px-8 py-4 bg-[#212120] text-white rounded-2xl text-[9px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-black transition-all active:scale-95">
                                Review & Sign
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <!-- No pending agreements -->
            <div class="bg-white p-20 rounded-[3rem] border border-dashed border-slate-200 text-center">
                <div class="w-20 h-20 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                    <i class="ti-face-smile"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">All set!</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">No pending agreements found.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>

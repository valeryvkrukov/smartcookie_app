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
            <div class="relative group bg-white rounded-[3rem] border {{ $req->status === 'Signed' ? 'border-slate-100 shadow-xl' : 'border-rose-100 shadow-2xl shadow-rose-200/30' }} transition-all duration-500 overflow-hidden"
                 x-data="{ pdfOpen: {{ $req->status !== 'Signed' ? 'true' : 'false' }} }">

                <div class="flex flex-col md:flex-row items-center justify-between gap-8 p-10">
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
                        {{-- ── Toggle: show/hide the inline PDF viewer --}}
                        <button type="button" @click="pdfOpen = !pdfOpen"
                                class="flex items-center gap-2 px-6 py-4 bg-slate-50 text-slate-400 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-100 transition-all active:scale-95">
                            <i :class="pdfOpen ? 'ti-eye-off' : 'ti-eye'" class="text-sm"></i>
                            <span x-text="pdfOpen ? 'Hide PDF' : 'View PDF'"></span>
                        </button>

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

                {{-- ── PDF viewer: inline embed, shown by default for unsigned documents --}}
                <div x-show="pdfOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="border-t border-slate-100">
                    <iframe src="{{ asset('storage/' . $req->agreement->pdf_path) }}#toolbar=0"
                            class="w-full"
                            style="height: 70vh; min-height: 500px;"
                            loading="lazy"
                            title="{{ $req->agreement->name }}">
                        <p class="p-6 text-sm text-slate-500">
                            Your browser cannot display this PDF inline.
                            <a href="{{ asset('storage/' . $req->agreement->pdf_path) }}" target="_blank"
                               class="underline text-indigo-600">Download the PDF</a> to view it.
                        </p>
                    </iframe>
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

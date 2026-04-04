<x-app-layout>
    <x-slot name="header_title">Compliance Control</x-slot>

    {{-- ── Upload: section for adding new agreement PDF documents --}}
    <div class="mb-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden"
         x-data="{ open: false }">

        <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-8 py-6 text-left transition-colors hover:bg-slate-50/60">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i class="ti-upload text-base"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900 tracking-tight">Upload New Agreement</p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                        {{ $documents->count() }} document{{ $documents->count() === 1 ? '' : 's' }} available
                    </p>
                </div>
            </div>
            <i :class="open ? 'ti-angle-up' : 'ti-angle-down'" class="text-slate-300 text-sm transition-transform"></i>
        </button>

        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="px-8 pb-8 border-t border-slate-50">

            {{-- Success / error flash --}}
            @if(session('success'))
                <div class="mt-6 px-5 py-3 bg-emerald-50 border border-emerald-100 rounded-2xl text-[11px] font-bold text-emerald-700">
                    <i class="ti-check mr-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mt-6 px-5 py-3 bg-rose-50 border border-rose-100 rounded-2xl text-[11px] font-bold text-rose-700">
                    <i class="ti-na mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <form action="{{ route('admin.agreements.store') }}" method="POST" enctype="multipart/form-data"
                  class="mt-6 flex flex-col sm:flex-row gap-4 items-end">
                @csrf
                <div class="flex-1 space-y-1">
                    <label class="label-premium">Document Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="input-premium @error('name') border-rose-300 @enderror"
                           placeholder="e.g. Tutoring Services Agreement 2026" required>
                    @error('name')
                        <p class="text-[10px] text-rose-500 font-bold">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex-1 space-y-1">
                    <label class="label-premium">PDF File <span class="text-slate-300 normal-case font-bold">(max 20 MB)</span></label>
                    <input type="file" name="pdf" accept=".pdf"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-[11px] font-bold text-slate-500
                                  file:mr-4 file:py-1.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest
                                  file:bg-[#212120] file:text-white hover:file:bg-black transition-all
                                  @error('pdf') border-rose-300 @enderror"
                           required>
                    @error('pdf')
                        <p class="text-[10px] text-rose-500 font-bold">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="px-8 py-3 bg-[#212120] text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-sm hover:bg-black transition-all flex-shrink-0">
                    Upload
                </button>
            </form>

            {{-- ── Documents list: existing uploaded agreements --}}
            @if($documents->isNotEmpty())
                <div class="mt-6 divide-y divide-slate-50 border border-slate-100 rounded-2xl overflow-hidden">
                    @foreach($documents as $doc)
                        <div class="bg-white hover:bg-slate-50/50 transition-colors"
                             x-data="{ replacing: false }">

                            {{-- Row: name + actions --}}
                            <div class="flex items-center justify-between px-5 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <i class="ti-file text-slate-300 text-sm flex-shrink-0"></i>
                                    <span class="text-[11px] font-bold text-slate-700 truncate">{{ $doc->name }}</span>
                                    <span class="text-[9px] font-bold text-slate-300 font-mono hidden sm:inline">{{ basename($doc->pdf_path) }}</span>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                                    <a href="{{ asset('storage/' . $doc->pdf_path) }}" target="_blank"
                                       class="text-[9px] font-black uppercase tracking-widest text-indigo-400 hover:text-indigo-700 transition-colors">
                                        Preview
                                    </a>
                                    <button type="button" @click="replacing = !replacing"
                                            class="text-[9px] font-black uppercase tracking-widest text-amber-400 hover:text-amber-700 transition-colors">
                                        Replace
                                    </button>
                                    {{-- Delete — blocked if signed copies exist --}}
                                    <form action="{{ route('admin.agreements.destroy', $doc) }}" method="POST"
                                          onsubmit="return confirm('Delete \'{{ addslashes($doc->name) }}\'? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[9px] font-black uppercase tracking-widest text-rose-300 hover:text-rose-600 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Replace sub-form (toggled) --}}
                            <div x-show="replacing"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 class="px-5 pb-4">
                                <form action="{{ route('admin.agreements.replace', $doc) }}" method="POST"
                                      enctype="multipart/form-data"
                                      class="flex flex-col sm:flex-row gap-3 items-end">
                                    @csrf
                                    <div class="flex-1 space-y-1">
                                        <label class="label-premium">New PDF File <span class="text-slate-300 normal-case font-bold">(replaces current)</span></label>
                                        <input type="file" name="pdf" accept=".pdf" required
                                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-[11px] font-bold text-slate-500
                                                      file:mr-4 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest
                                                      file:bg-amber-500 file:text-white hover:file:bg-amber-600 transition-all">
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit"
                                                class="px-5 py-2.5 bg-amber-500 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-600 transition-all">
                                            Upload
                                        </button>
                                        <button type="button" @click="replacing = false"
                                                class="px-5 py-2.5 bg-slate-100 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Filters and Search -->
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

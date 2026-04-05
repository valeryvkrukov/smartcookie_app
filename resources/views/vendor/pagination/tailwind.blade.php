@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-col sm:flex-row items-center justify-between gap-4">

        {{-- Results summary --}}
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            @if ($paginator->firstItem())
                Showing <span class="font-black text-slate-600">{{ $paginator->firstItem() }}</span>
                – <span class="font-black text-slate-600">{{ $paginator->lastItem() }}</span>
                of <span class="font-black text-slate-600">{{ $paginator->total() }}</span>
            @else
                {{ $paginator->count() }} results
            @endif
        </p>

        {{-- Page links --}}
        <div class="flex items-center gap-1.5">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-100 text-slate-200 cursor-not-allowed" aria-disabled="true">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-100 text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-all shadow-sm"
                   aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center w-9 h-9 text-[10px] font-black text-slate-300 tracking-widest">
                        &hellip;
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-[#212120] text-white text-[10px] font-black tracking-wide shadow-sm cursor-default">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-100 text-[10px] font-black text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-all shadow-sm">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-100 text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-all shadow-sm"
                   aria-label="{{ __('pagination.next') }}">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-100 text-slate-200 cursor-not-allowed" aria-disabled="true">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </span>
            @endif

        </div>

    </nav>
@endif

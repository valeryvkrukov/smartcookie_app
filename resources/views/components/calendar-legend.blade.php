@props(['showNoCredits' => false])

<div class="mb-6 flex flex-wrap items-center gap-x-6 gap-y-2 px-1">
    <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mr-2">Legend</span>

    <span class="inline-flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-[#4f46e5] flex-shrink-0"></span>
        <span class="text-[10px] font-bold text-slate-500">Scheduled</span>
    </span>

    <span class="inline-flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-[#6366f1] flex-shrink-0"></span>
        <span class="text-[10px] font-bold text-slate-500">↻ Recurring</span>
    </span>

    <span class="inline-flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-[#f59e0b] flex-shrink-0"></span>
        <span class="text-[10px] font-bold text-slate-500">Initial</span>
    </span>

    <span class="inline-flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-[#10b981] flex-shrink-0"></span>
        <span class="text-[10px] font-bold text-slate-500">Completed / Billed</span>
    </span>

    <span class="inline-flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-[#94a3b8] flex-shrink-0"></span>
        <span class="text-[10px] font-bold text-slate-500">Cancelled</span>
    </span>

    @if($showNoCredits)
    <span class="inline-flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-[#ef4444] flex-shrink-0"></span>
        <span class="text-[10px] font-bold text-slate-500">No Credits</span>
    </span>
    @endif
</div>

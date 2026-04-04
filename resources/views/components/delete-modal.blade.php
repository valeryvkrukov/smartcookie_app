<div x-data="{
    open: false,
    name: '',
    formId: null,
    isRecurring: false,
    useAjax: false,

    performDelete(deleteSeries = false) {
        const form = document.getElementById(this.formId);
        if (!form) return;

        if (!this.useAjax) {
            if (deleteSeries) {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'delete_series'; inp.value = '1';
                form.appendChild(inp);
            }
            form.submit();
            return;
        }

        const formData = new FormData(form);
        if (deleteSeries) formData.append('delete_series', '1');

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.open = false;
                if (window.calendar) window.calendar.refetchEvents();
            }
        })
        .catch(() => {});
    }
}"
     @confirm-delete.window="open = true; name = ($event.detail && $event.detail.name) ? $event.detail.name : ''; formId = ($event.detail && $event.detail.formId) ? $event.detail.formId : null; isRecurring = ($event.detail && $event.detail.isRecurring) ? $event.detail.isRecurring : false; useAjax = ($event.detail && $event.detail.useAjax) ? true : false"
     @confirm-user-delete.window="open = true; name = ($event.detail && $event.detail.name) ? $event.detail.name : ''; formId = ($event.detail && $event.detail.formId) ? $event.detail.formId : null; isRecurring = false; useAjax = false"
     x-show="open" 
     x-cloak
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto"
     style="display: none !important;">
    
    {{-- ── Backdrop: fixed overlay with blur, closes modal on click --}}
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="open = false"></div>

    <div x-show="open" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="relative w-full max-w-sm bg-white p-10 rounded-[3rem] shadow-2xl border border-slate-100 text-center">
        
        <div class="w-20 h-20 bg-rose-50 text-rose-600 rounded-3xl flex items-center justify-center text-3xl mx-auto mb-6 shadow-sm">
            <i class="ti-trash"></i>
        </div>

        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Are you sure?</h3>
        <p class="mt-4 text-slate-500 leading-relaxed text-sm">
            You are about to delete <br>
            <span class="font-bold text-slate-900" x-text="name"></span>.
        </p>

        <div class="mt-10 space-y-3">
            {{-- ── Delete button: removes single instance (or item if not recurring) --}}
            <button type="button" @click="performDelete(false)"
                    class="w-full flex items-center justify-center py-4 bg-rose-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-rose-700 transition shadow-lg shadow-rose-100 leading-none">
                <span x-text="isRecurring ? 'Only This Instance' : 'Confirm & Delete'"></span>
            </button>

            {{-- ── Series button: visible only for recurring, deletes all future --}}
            <template x-if="isRecurring">
                <button type="button" 
                        @click="performDelete(true)"
                        class="w-full flex items-center justify-center py-4 bg-white border-2 border-rose-600 text-rose-600 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-rose-50 transition leading-none">
                    Delete All Future Sessions
                </button>
            </template>

            <button type="button" @click="open = false"
                    class="w-full py-4 text-slate-400 text-[10px] font-bold uppercase tracking-widest hover:text-slate-600 transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

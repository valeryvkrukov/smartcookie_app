<div @set-error.window="errorMessage = $event.detail.message; setTimeout(() => errorMessage = '', 5000)">
    <div x-show="errorMessage" 
        x-transition
        class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center space-x-3 text-rose-600 shadow-sm"
        style="display: none;">
        <i class="ti-alert text-lg"></i>
        <span class="text-[10px] font-black uppercase tracking-widest" x-text="errorMessage"></span>
    </div>

    <form :action="isEdit ? '/tutor/sessions/' + sessionId : '{{ route('tutor.sessions.store') }}'" method="POST" class="space-y-6">
        @csrf
        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Date</label>
            <input type="date" name="date" x-model="date" class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800" required>
        </div>
        <input type="hidden" name="location" value="Online">

        <template x-if="isEdit">
            <input type="hidden" name="_method" value="PUT">
        </template>

        <!-- Student Selection (loaded via ViewComposer) -->
        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Student</label>
            
            <select name="student_id" x-model="studentId" required class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800">
                <option value="">Choose student...</option>
                @foreach($students as $s)
                    <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Subject -->
        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Subject</label>
            <input type="text" name="subject" placeholder="Math, English..." class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800" required>
        </div>

        <!-- Time (linked with Alpine variables) -->
        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Start Time</label>
            <div class="flex items-center space-x-3 bg-slate-50 rounded-2xl p-2">
                <select name="time_h" x-model="time_h" class="bg-transparent border-0 focus:ring-0 font-black text-xl">
                    @for($i=1; $i<=12; $i++) <option value="{{ sprintf('%02d', $i) }}">{{ $i }}</option> @endfor
                </select>
                <span class="text-slate-300 font-black">:</span>
                <select name="time_m" x-model="time_m" class="bg-transparent border-0 focus:ring-0 font-black text-xl">
                    @foreach(['00','15','30','45'] as $m) <option value="{{ $m }}">{{ $m }}</option> @endforeach
                </select>
                <select name="time_ampm" x-model="time_ampm" class="bg-[#212120] text-white rounded-xl text-[12px] font-black px-8 py-2">
                    <option value="AM">AM</option><option value="PM">PM</option>
                </select>
            </div>
        </div>

        <!-- Duration (Segmented Control) -->
        <div class="grid grid-cols-4 gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-100">
            @foreach(['0:30' => '30m', '1:00' => '1h', '1:30' => '1.5h', '2:00' => '2h'] as $val => $lbl)
                <label class="flex-1">
                    <input type="radio" name="duration" value="{{ $val }}" {{ $val == '1:00' ? 'checked' : '' }} class="peer hidden">
                    <div class="cursor-pointer text-center py-2 text-[10px] font-black uppercase text-slate-400 peer-checked:bg-white peer-checked:text-[#212120] peer-checked:shadow-sm rounded-xl transition-all">
                        {{ $lbl }}
                    </div>
                </label>
            @endforeach
        </div>

        <!-- SEND BUTTON (Fetch API) -->
        <button type="button" 
            @click="
                const form = $el.closest('form');
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST', 
                    body: formData,
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(res => {
                    if(res.status === 200 && res.body.success) {
                        open = false;
                        if(window.calendar) window.calendar.refetchEvents();
                        errorMessage = '';
                    } else {
                        $dispatch('set-error', { message: res.body.message || 'Error occurred' });
                    }
                })
                .catch(err => $dispatch('set-error', { message: 'Connection error' }))
            "
            class="btn-primary mt-6">
             <span x-text="isEdit ? 'Update Session' : 'Create Session'"></span>
        </button>

        <template x-if="isEdit">
            <button type="button" 
                onclick="window.dispatchEvent(new CustomEvent('confirm-delete', { 
                    detail: {
                        name: 'this tutoring session', 
                        formId: 'delete-session-form', 
                        isRecurring: isRecurring 
                    }
                }))"
                class="text-[10px] font-black uppercase tracking-widest text-rose-500 hover:text-rose-700 transition-colors">
                Cancel Session
            </button>
        </template>
    </form>

    <form id="delete-session-form" :action="'/tutor/sessions/' + sessionId" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
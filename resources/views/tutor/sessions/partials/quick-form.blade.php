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
        
        <template x-if="isEdit">
            <input type="hidden" name="_method" value="PUT">
        </template>

        <!-- Student Selection (loaded via ViewComposer) -->
        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Student</label>
            
            <select name="student_id" x-model="studentId" required
                @change="studentTimezone = (window.studentTimezoneMap || {})[$event.target.value] || ''"
                class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800">
                <option value="">Choose student...</option>
                @foreach($students as $s)
                    <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Subject -->
        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Subject</label>
            <input type="text" name="subject" x-model="subject" placeholder="Math, English..." class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800" required>
        </div>

        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Date</label>
            <input type="date" name="date" x-model="date" class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800" required>
        </div>

        <!-- Timezone offset indicator -->
        <template x-if="studentTimezone">
            <div class="flex items-center justify-between bg-slate-50 rounded-2xl px-4 py-3 border border-slate-100">
                <div class="flex items-center space-x-2 text-slate-400">
                    <i class="ti-time text-sm"></i>
                    <span class="text-[9px] font-black uppercase tracking-widest" x-text="studentTimezone"></span>
                </div>
                <div class="text-[9px] uppercase tracking-widest" x-html="tzOffsetLabel(studentTimezone)"></div>
            </div>
        </template>

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

        <!-- Duration (Segmented Control) — x-model keeps selection in sync when editing -->
        <div class="grid grid-cols-4 gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-100">
            @foreach(['0:30' => '30m', '1:00' => '1h', '1:30' => '1.5h', '2:00' => '2h'] as $val => $lbl)
                <label class="flex-1">
                    <input type="radio" name="duration" value="{{ $val }}" x-model="duration" class="peer hidden">
                    <div class="cursor-pointer text-center py-2 text-[10px] font-black uppercase text-slate-400 peer-checked:bg-white peer-checked:text-[#212120] peer-checked:shadow-sm rounded-xl transition-all">
                        {{ $lbl }}
                    </div>
                </label>
            @endforeach
        </div>

        <!-- Location -->
        <div>
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Location <span class="text-slate-300 font-normal normal-case tracking-normal">(optional)</span></label>
            <input type="text" name="location" x-model="sessionLocation" placeholder="Online" class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800">
        </div>

        <!-- Recurring series edit option — only shown when editing a recurring session -->
        <template x-if="isEdit && isRecurring">
            <div x-data="{ updateSeries: false }">
                <input type="hidden" name="update_series" :value="updateSeries ? '1' : ''">
                <div class="bg-amber-50 border border-amber-100 rounded-2xl px-4 py-3 space-y-3">
                    <p class="text-[9px] font-black uppercase tracking-widest text-amber-700">
                        <i class="ti-reload mr-1"></i> Recurring session
                    </p>
                    <div class="flex gap-2">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" :checked="!updateSeries" @change="updateSeries = false" class="sr-only">
                            <div class="text-center py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all"
                                 :class="!updateSeries ? 'bg-white shadow text-slate-900' : 'text-slate-400 hover:text-slate-600'">
                                Only this session
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" :checked="updateSeries" @change="updateSeries = true" class="sr-only">
                            <div class="text-center py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all"
                                 :class="updateSeries ? 'bg-amber-500 text-white shadow' : 'text-slate-400 hover:text-slate-600'">
                                All future sessions
                            </div>
                        </label>
                    </div>
                    <p x-show="updateSeries" class="text-[9px] text-amber-600 leading-relaxed">
                        Changing the date will shift all future sessions by the same number of days.
                    </p>
                </div>
            </div>
        </template>

        <!-- FLAGS -->
        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2" :class="recurringWeekly ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'">
                <input type="checkbox" name="is_initial" value="1"
                       :checked="isInitial"
                       :disabled="recurringWeekly"
                       @change="isInitial = $event.target.checked; if (isInitial) recurringWeekly = false"
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Initial Session</span>
            </label>
            <template x-if="!isEdit">
                <label class="flex items-center gap-2" :class="isInitial ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'">
                    <input type="checkbox" name="recurs_weekly" value="1"
                           :checked="recurringWeekly"
                           :disabled="isInitial"
                           @change="recurringWeekly = $event.target.checked; if (recurringWeekly) isInitial = false"
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Recurring (weekly ×12)</span>
                </label>
            </template>
        </div>

        <!-- SEND BUTTON (Fetch API) — disabled on submit to prevent double-click -->
        <button type="button" 
            @click="
                $el.disabled = true;
                const origHtml = $el.innerHTML;
                $el.innerHTML = '<span class=\'inline-flex items-center justify-center\'><i class=\'ti-reload animate-spin mr-2 text-sm\' style=\'display:inline-block;line-height:1\'></i> SAVING...</span>';

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
                    if (res.status === 200 && res.body.success) {
                        $el.disabled = false;
                        $el.innerHTML = origHtml;
                        open = false;
                        if (window.calendar) window.calendar.refetchEvents();
                        errorMessage = '';
                    } else {
                        $dispatch('set-error', { message: res.body.message || 'Error occurred' });
                        $el.disabled = false;
                        $el.innerHTML = origHtml;
                    }
                })
                .catch(() => {
                    $dispatch('set-error', { message: 'Connection error' });
                    $el.disabled = false;
                    $el.innerHTML = origHtml;
                })
            "
            class="btn-primary mt-6">
             <span x-text="isEdit ? 'Update Session' : 'Create Session'"></span>
        </button>

        <template x-if="isEdit">
            <button type="button"
                @click="$dispatch('confirm-delete', { 
                    name: 'this tutoring session', 
                    formId: 'delete-session-form', 
                    isRecurring: isRecurring,
                    useAjax: true
                })"
                class="w-full py-3.5 rounded-2xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 hover:text-rose-700 text-[10px] font-black uppercase tracking-widest transition-colors">
                <i class="ti-trash mr-1.5"></i> Delete Session
            </button>
        </template>
    </form>

    <form id="delete-session-form" :action="'/tutor/sessions/' + sessionId" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
<div>
    <div x-show="errorMessage" 
        x-transition
        class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center space-x-3 text-rose-600 shadow-sm"
        style="display: none;">
        <i class="ti-alert text-lg"></i>
        <span class="text-[10px] font-black uppercase tracking-widest" x-text="errorMessage"></span>
    </div>

    <form :action="isEdit ? '/admin/sessions/' + sessionId : '/admin/sessions'" method="POST" class="space-y-6">
        @csrf
        
        <template x-if="isEdit">
            <input type="hidden" name="_method" value="PUT">
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="label-premium">Tutor</label>
                <select x-model="tutorId" name="tutor_id" class="input-premium text-sm" required>
                    <option value="">Select Tutor</option>
                    @foreach($tutors as $t) <option value="{{ $t->id }}">{{ $t->full_name }}{{ $t->role === 'admin' ? ' ★' : '' }}</option> @endforeach
                </select>
            </div>
            <div class="space-y-1">
                <label class="label-premium">Student</label>
                <select x-model="studentId" name="student_id" class="input-premium text-sm" required
                    @change="studentTimezone = (window.studentTimezoneMap || {})[$event.target.value] || ''">
                    <option value="">Select Student</option>
                    @foreach($students as $s) <option value="{{ $s->id }}">{{ $s->full_name }}</option> @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-1">
            <label class="label-premium">Subject</label>
            <input x-model="subject" type="text" name="subject" class="input-premium" placeholder="e.g. Mathematics" required>
        </div>

        <div class="space-y-1">
            <label class="label-premium">Date</label>
            <input type="date" name="date" x-model="date" class="input-premium text-sm" required>
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

        <!-- Time (using Alpine variables from modal-container) -->
        <div class="flex items-center space-x-3 bg-slate-50 rounded-2xl p-2">
            <select name="time_h" x-model="time_h" class="bg-transparent border-0 focus:ring-0 font-black text-xl flex-1">
                @for($i=1; $i<=12; $i++) <option value="{{ sprintf('%02d', $i) }}">{{ $i }}</option> @endfor
            </select>
            <span class="text-slate-300 font-black">:</span>
            <select name="time_m" x-model="time_m" class="bg-transparent border-0 focus:ring-0 font-black text-xl flex-1">
                @foreach(['00','15','30','45'] as $m) <option value="{{ $m }}">{{ $m }}</option> @endforeach
            </select>
            <select name="time_ampm" x-model="time_ampm" class="bg-[#212120] text-white rounded-xl text-[12px] font-black px-8 py-2">
                <option value="AM">AM</option><option value="PM">PM</option>
            </select>
        </div>

        <!-- Duration -->
        <div class="flex p-1 bg-slate-100 rounded-2xl">
            @foreach([30 => '30m', 60 => '1h', 90 => '1.5h', 120 => '2h'] as $val => $lbl)
                <label class="flex-1">
                    <input type="radio" name="duration" value="{{ $val }}" x-model="duration" class="peer hidden">
                    <div class="cursor-pointer text-center py-2 text-[10px] font-black text-slate-400 peer-checked:bg-white peer-checked:text-[#212120] peer-checked:shadow-sm rounded-xl transition-all uppercase tracking-widest">{{ $lbl }}</div>
                </label>
            @endforeach
        </div>

        <!-- Location -->
        <div class="space-y-1">
            <label class="label-premium">Location <span class="text-slate-300 text-[8px] normal-case tracking-normal font-normal">optional</span></label>
            <input type="text" name="location" x-model="sessionLocation" placeholder="Online" class="input-premium">
        </div>

        <!-- Status (edit only) -->
        <template x-if="isEdit">
            <div class="space-y-1">
                <label class="label-premium">Status</label>
                <div class="grid grid-cols-4 gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-100">
                    @foreach(['Scheduled' => 'indigo', 'Completed' => 'emerald', 'Billed' => 'teal', 'Cancelled' => 'slate'] as $st => $color)
                        <label class="flex-1">
                            <input type="radio" name="status" value="{{ $st }}" x-model="sessionStatus" class="peer hidden">
                            <div class="cursor-pointer text-center py-2 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all
                                        peer-checked:shadow-sm
                                        @if($color === 'indigo') peer-checked:bg-indigo-600 peer-checked:text-white text-indigo-400
                                        @elseif($color === 'emerald') peer-checked:bg-emerald-500 peer-checked:text-white text-emerald-400
                                        @elseif($color === 'teal') peer-checked:bg-teal-500 peer-checked:text-white text-teal-400
                                        @else peer-checked:bg-slate-400 peer-checked:text-white text-slate-400 @endif">
                                {{ $st }}
                            </div>
                        </label>
                    @endforeach
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

        <!-- Recurring series edit option — only shown when editing a recurring session -->
        <template x-if="isEdit && isRecurring">
            <div>
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

        <button type="button" @click="submitModalForm($el)" class="btn-primary">
            <span x-text="isEdit ? 'Update Session' : 'Create Session'"></span>
        </button>

        <template x-if="isEdit">
            <button type="button"
                @click="$dispatch('confirm-delete', { name: subject + ' with ' + ($el.closest('form').querySelector('[name=student_id]').selectedOptions[0]?.text || 'student'), formId: 'delete-session-form', isRecurring: false, useAjax: true })"
                class="w-full py-3.5 rounded-2xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 hover:text-rose-700 text-[10px] font-black uppercase tracking-widest transition-colors">
                <i class="ti-trash mr-1.5"></i> Delete Session
            </button>
        </template>
    </form>

    <form id="delete-session-form" :action="'/admin/sessions/' + sessionId" method="POST" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" name="delete_series" :value="updateSeries ? '1' : ''">
    </form>
</div>

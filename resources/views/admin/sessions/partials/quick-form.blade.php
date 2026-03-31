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
        <input type="hidden" name="date" :value="date">
        <input type="hidden" name="location" value="Online">

        <template x-if="isEdit">
            <input type="hidden" name="_method" value="PUT">
        </template>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="label-premium">Tutor</label>
                <select x-model="tutorId" name="tutor_id" class="input-premium text-sm" required>
                    <option value="">Select Tutor</option>
                    @foreach($tutors as $t) <option value="{{ $t->id }}">{{ $t->full_name }}</option> @endforeach
                </select>
            </div>
            <div class="space-y-1">
                <label class="label-premium">Student</label>
                <select x-model="studentId" name="student_id" class="input-premium text-sm" required>
                    <option value="">Select Student</option>
                    @foreach($students as $s) <option value="{{ $s->id }}">{{ $s->full_name }}</option> @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-1">
            <label class="label-premium">Subject</label>
            <input x-model="subject" type="text" name="subject" class="input-premium" placeholder="e.g. Mathematics" required>
        </div>

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
            <template x-for="val in ['0:30', '1:00', '1:30', '2:00']">
                <label class="flex-1">
                    <input type="radio" name="duration" :value="val" x-model="duration" class="peer hidden">
                    <div class="cursor-pointer text-center py-2 text-[10px] font-black text-slate-400 peer-checked:bg-white peer-checked:text-[#212120] peer-checked:shadow-sm rounded-xl transition-all uppercase tracking-widest" x-text="val"></div>
                </label>
            </template>
        </div>

        <button type="button" @click="submitModalForm($el)" class="btn-primary">
            <span x-text="isEdit ? 'Update Session' : 'Create Session'"></span>
        </button>

        <template x-if="isEdit">
            <button type="button" 
                    @click="$dispatch('confirm-delete', { 
                        name: 'this tutoring session', 
                        formId: 'delete-session-form', 
                        isRecurring: isRecurring 
                    })"
                    class="text-[10px] font-black uppercase tracking-widest text-rose-500 hover:text-rose-700 transition-colors">
                Cancel Session
            </button>
        </template>
    </form>

    <form id="delete-session-form" :action="'/admin/sessions/' + sessionId" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

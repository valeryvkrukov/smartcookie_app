<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Dashboard >
        </a>
        <a href="{{ route('tutor.students.index') }}" class="text-gray-500 hover:text-gray-700 transition">
            Assigned Students >
        </a>
        Schedule New Session
    </x-slot>
    
    <div class="max-w-lg mx-auto py-8 px-4">
        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl shadow-slate-200/50 p-8 md:p-12">
            
            <form action="{{ route('tutor.sessions.store') }}" method="POST" class="space-y-10">
                @csrf

                {{-- ── Student: dropdown to select the assigned student --}}
                <div class="space-y-3">
                    <x-input-label value="Student" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1" />
                    <select name="student_id" required class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 px-1 text-lg font-bold text-slate-800 transition-colors">
                        <option value="">Choose student...</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ (request('student_id') == $s->id || (isset($selectedStudent) && $selectedStudent->id == $s->id)) ? 'selected' : '' }}>
                                {{ $s->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ── Subject & date row: side-by-side grid on medium+ screens --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-3">
                        <x-input-label value="Subject" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1" />
                        <input type="text" name="subject" placeholder="e.g. Math" class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 px-1 text-base font-bold text-slate-800" required />
                    </div>
                    <div class="space-y-3">
                        <x-input-label value="Date" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1" />
                        <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 px-1 text-base font-bold text-slate-800" required />
                    </div>
                </div>

                {{-- ── Start time: hour/minute selects with AM/PM toggle --}}
                <div class="space-y-3">
                    <x-input-label value="Start Time" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1" />
                    <div class="flex items-center space-x-4">
                        <div class="flex-1 flex items-center bg-slate-50 rounded-2xl px-4 py-1">
                            <select name="time_h" class="bg-transparent border-0 focus:ring-0 font-black text-xl text-slate-800">
                                @for($i=1; $i<=12; $i++) <option value="{{ sprintf('%02d', $i) }}">{{ $i }}</option> @endfor
                            </select>
                            <span class="font-black text-slate-300">:</span>
                            <select name="time_m" class="bg-transparent border-0 focus:ring-0 font-black text-xl text-slate-800">
                                @foreach(['00','15','30','45'] as $m) <option value="{{ $m }}">{{ $m }}</option> @endforeach
                            </select>
                        </div>
                        <select name="time_ampm" class="w-24 py-4 bg-[#212120] text-white rounded-2xl text-center font-black text-xs uppercase tracking-widest border-0 focus:ring-0">
                            <option value="AM">AM</option>
                            <option value="PM">PM</option>
                        </select>
                    </div>
                </div>

                {{-- ── Options: recurring and initial-consultation checkboxes --}}
                <div class="pt-6 space-y-4">
                    <label class="flex items-center group cursor-pointer">
                        <input type="checkbox" name="recurs_weekly" value="1" class="w-5 h-5 rounded border-2 border-slate-200 text-[#212120] focus:ring-0 transition-all mr-3">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest group-hover:text-slate-800 transition-colors">Recurs Weekly (12 weeks)</span>
                    </label>
                    <label class="flex items-center group cursor-pointer">
                        <input type="checkbox" name="is_initial" value="1" class="w-5 h-5 rounded border-2 border-slate-200 text-emerald-500 focus:ring-0 transition-all mr-3">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest group-hover:text-slate-800 transition-colors">Initial Consultation</span>
                    </label>
                </div>

                {{-- ── Submit button: schedules the session --}}
                <button type="submit" class="w-full py-5 bg-[#212120] text-white rounded-2xl font-black uppercase tracking-[0.3em] text-[11px] shadow-xl hover:bg-black active:scale-[0.98] transition-all">
                    Schedule Session
                </button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialCheck = document.getElementById('is_initial');
        const durationInputs = document.querySelectorAll('input[name="duration"]');
        const recursWeekly = document.getElementById('recurs_weekly');

        if (!initialCheck) {
            console.error('Element is_initial not found!');
            return;
        }

        initialCheck.addEventListener('change', function() {
            if(this.checked) {
                const oneHourInput = document.querySelector('input[name="duration"][value="60"]');
                if (oneHourInput) {
                    oneHourInput.checked = true;
                    oneHourInput.dispatchEvent(new Event('change')); 
                }
                durationInputs.forEach(input => {
                    if(input.value !== '60') {
                        input.disabled = true;
                        input.parentElement.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                });
                recursWeekly.checked = false;
                recursWeekly.disabled = true;
                recursWeekly.parentElement.classList.add('opacity-50');

            } else {
                durationInputs.forEach(input => {
                    input.disabled = false;
                    input.parentElement.classList.remove('opacity-50', 'cursor-not-allowed');
                });
                recursWeekly.disabled = false;
                recursWeekly.parentElement.classList.remove('opacity-50');
            }
        });
    });
    </script>

</x-app-layout>

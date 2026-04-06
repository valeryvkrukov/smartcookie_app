<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Admin Control Center >
        </a>
        <a href="{{ route('admin.calendar.index') }}" class="text-gray-500 hover:text-gray-700 transition">
            Calendar >
        </a>
        Schedule New Session
    </x-slot>

    <div class="max-w-2xl bg-white p-8 shadow-sm border border-slate-200 rounded-2xl">
        <form action="{{ route('admin.sessions.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- TUTOR -->
                <div>
                    <x-input-label value="Assign Tutor" class="text-[10px] font-bold uppercase" />
                    <select name="tutor_id" required class="w-full border-slate-200 rounded-lg text-sm">
                        <option value="">Select Tutor...</option>
                        @foreach($tutors as $t)
                            <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- STUDENT -->
                <div>
                    <x-input-label value="Assign Student" class="text-[10px] font-bold uppercase" />
                    <select name="student_id" required class="w-full border-slate-200 rounded-lg text-sm">
                        <option value="">Select Student...</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- SUBJECT -->
            <div>
                <x-input-label value="Subject" class="text-[10px] font-bold uppercase" />
                <x-text-input name="subject" class="w-full text-sm" placeholder="Math, English, etc." required />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- DATE -->
                <div>
                    <x-input-label value="Date" class="text-[10px] font-bold uppercase" />
                    <x-text-input type="date" name="date" value="{{ $selectedDate }}" class="w-full text-sm" required />
                </div>

                <!-- DURATION -->
                <div>
                    <x-input-label value="Duration" class="text-[10px] font-bold uppercase" />
                    <select name="duration" class="w-full border-slate-200 rounded-lg text-sm">
                        <option value="1:00">1:00 hour</option>
                        <option value="0:30">0:30 mins</option>
                        <option value="1:30">1:30 hours</option>
                        <option value="2:00">2:00 hours</option>
                    </select>
                </div>
            </div>

            <!-- START TIME -->
            <div>
                <x-input-label value="Start Time" class="text-[10px] font-bold uppercase mb-1" />
                <div class="flex space-x-2">
                    <select name="time_h" class="border-slate-200 rounded-lg text-sm">
                        @for($i=1; $i<=12; $i++) <option value="{{ sprintf('%02d', $i) }}">{{ $i }}</option> @endfor
                    </select>
                    <select name="time_m" class="border-slate-200 rounded-lg text-sm">
                        <option value="00">00</option><option value="15">15</option>
                        <option value="30">30</option><option value="45">45</option>
                    </select>
                    <select name="time_ampm" class="border-slate-200 rounded-lg text-sm">
                        <option value="AM">AM</option><option value="PM">PM</option>
                    </select>
                </div>
            </div>

            <!-- FLAGS -->
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_initial" value="1"
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Initial Session</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="recurs_weekly" value="1"
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Recurring (weekly × 12)</span>
                </label>
            </div>

            <div class="pt-4 border-t flex justify-end">
                <x-primary-button class="bg-[#212120]">Create Session</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Admin Control Center >
        </a>
        <a href="{{ route('admin.calendar.index') }}" class="text-gray-500 hover:text-gray-700 transition">
            Calendar >
        </a>
        Edit Session #{{ $session->id }}
    </x-slot>

    <div class="max-w-2xl bg-white p-8 shadow-sm border border-slate-200 rounded-2xl">
        @if ($errors->any())
            <div class="bg-red-500 text-white p-4 mb-4 rounded-xl font-bold text-xs">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('admin.sessions.update', $session->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Subject (Math, English, etc.) -->
            <div class="mt-4">
                <x-input-label value="Subject" class="text-[10px] font-bold uppercase" />
                <x-text-input name="subject" value="{{ old('subject', $session->subject) }}" class="w-full text-sm" required />
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="Tutor" class="text-[10px] font-bold uppercase" />
                    <select name="tutor_id" class="w-full border-slate-200 rounded-lg text-sm">
                        @foreach($tutors as $t)
                            <option value="{{ $t->id }}" {{ $session->tutor_id == $t->id ? 'selected' : '' }}>{{ $t->full_name }}{{ $t->role === 'admin' ? ' ★' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Student" class="text-[10px] font-bold uppercase" />
                    <select name="student_id" class="w-full border-slate-200 rounded-lg text-sm">
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ $session->student_id == $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="Date" class="text-[10px] font-bold uppercase" />
                    <x-text-input type="date" name="date" value="{{ $session->date->format('Y-m-d') }}" class="w-full text-sm" />
                </div>
                <div>
                    <x-input-label value="Duration" class="text-[10px] font-bold uppercase" />
                    <select name="duration" class="w-full border-slate-200 rounded-lg text-sm">
                        @foreach([30 => '30m (0.5 cr)', 60 => '1h (1 cr)', 90 => '1.5h (1.5 cr)', 120 => '2h (2 cr)'] as $d => $label)
                            <option value="{{ $d }}" {{ $session->duration == $d ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Time (with breaking into H:M AM/PM) -->
            @php 
                $carbonTime = \Carbon\Carbon::parse($session->start_time);
            @endphp
            <div class="flex space-x-2">
                <div>
                    <x-input-label value="Start Time" class="text-[10px] font-bold uppercase" />
                
                    <select name="time_h" class="border-slate-200 rounded-lg text-sm">
                        @for($i=1; $i<=12; $i++) 
                            <option value="{{ sprintf('%02d', $i) }}" {{ $carbonTime->format('h') == $i ? 'selected' : '' }}>{{ $i }}</option> 
                        @endfor
                    </select>
                    <select name="time_m" class="border-slate-200 rounded-lg text-sm">
                        @foreach(['00','15','30','45'] as $m)
                            <option value="{{ $m }}" {{ $carbonTime->format('i') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    <select name="time_ampm" class="border-slate-200 rounded-lg text-sm">
                        <option value="AM" {{ $carbonTime->format('A') == 'AM' ? 'selected' : '' }}>AM</option>
                        <option value="PM" {{ $carbonTime->format('A') == 'PM' ? 'selected' : '' }}>PM</option>
                    </select>
                </div>
            </div>

            <!-- FLAGS -->
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_initial" value="1"
                           {{ old('is_initial', $session->is_initial) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Initial Session</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="recurs_weekly" value="1"
                           {{ old('recurs_weekly', $session->recurs_weekly) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Recurring (weekly)</span>
                </label>
            </div>

            <div class="pt-4 border-t flex justify-between">
                <button type="button" 
                    onclick="window.dispatchEvent(new CustomEvent('confirm-delete', { 
                        detail: { 
                            name: 'this session', 
                            formId: 'delete-session-{{ $session->id }}', 
                            isRecurring: {{ $session->recurring_id ? 'true' : 'false' }} 
                        } 
                    }))"
                    class="text-xs text-red-600 font-bold uppercase hover:underline">
                    Cancel Session
                </button>
                
                <x-primary-button class="bg-[#212120]">Update Session</x-primary-button>
            </div>
        </form>

        <form id="delete-session-{{ $session->id }}" action="{{ route('admin.sessions.destroy', $session->id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</x-app-layout>

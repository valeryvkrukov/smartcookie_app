<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Dashboard >
        </a>
        Calendar
    </x-slot>

    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-800">Sessions</h3>
        </div>
        <div class="p-6 mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row gap-6">
            
            <!-- MAIN CALENDAR -->
            <div class="flex-1 bg-white p-6 shadow rounded-lg">
                <x-calendar-legend />
                <div id="calendar" style="min-height: 600px;"></div>
            </div>

            <!-- RIGHT PANEL: NEXT 5 -->
            <div class="w-full md:w-80 space-y-4">
                <div class="bg-white p-6 shadow rounded-lg">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2 italic text-indigo-600 underline uppercase">Next 5 Sessions</h3>
                    @forelse($nextSessions as $session)
                        <div class="mb-4 p-3 border-l-4 border-indigo-500 bg-gray-50 rounded shadow-sm">
                            <div class="text-xs font-bold">{{ $session->date->format('M d') }} @ {{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }}</div>
                            <div class="font-semibold text-sm">{{ $session->student->full_name }}</div>
                            <div class="text-[10px] text-gray-500 uppercase">{{ $session->subject }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No sessions scheduled</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                plugins: [ 
                    FullCalendar.dayGridPlugin, 
                    FullCalendar.timeGridPlugin, 
                    FullCalendar.interactionPlugin 
                ],
                initialView: 'timeGridWeek',
                timeZone: '{{ auth()->user()->time_zone ?? "local" }}',
                firstDay: 0, // Sunday
                allDaySlot: false,
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay'
                },
                events: "{{ route('tutor.calendar.events') }}",
                dateClick: function(info) {
                    window.dispatchEvent(new CustomEvent('open-session-modal', { 
                        detail: { date: info.dateStr } 
                    }));
                }
            });
            
            calendar.render();
            window.calendar = calendar;
        });
    </script>
    @endpush
</x-app-layout>

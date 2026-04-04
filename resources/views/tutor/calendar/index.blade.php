<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Dashboard >
        </a>
        My Teaching Schedule
    </x-slot>

    <div class="bg-white p-6 rounded-[2.5rem] border border-slate-200 shadow-sm">
        <x-calendar-legend />
        <div id="calendar" class="min-h-[700px]"></div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            // student_id -> timezone map, used by the modal to show the offset badge
            window.studentTimezoneMap = @json($students->pluck('time_zone', 'id'));
            
            // Define it globall to allow window.calendar.refetchEvents() from modals
            window.calendar = new FullCalendar.Calendar(calendarEl, {
                plugins: [ 
                    FullCalendar.dayGridPlugin, 
                    FullCalendar.timeGridPlugin, 
                    FullCalendar.interactionPlugin 
                ],
                // Plugins: time grid, daygrid and interaction (for clicks)
                //plugins: [ 'dayGrid', 'timeGrid', 'interaction' ],
                initialView: 'timeGridWeek',
                timeZone: '{{ auth()->user()->time_zone ?? "local" }}',
                
                // Define working hours and hide all-day slot
                lazyFetching: false,
                firstDay: 0, 
                allDaySlot: false,
                slotMinTime: '06:00:00', // Beginning of workday
                slotMaxTime: '22:00:00', // End of workday
                
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },

                // Data source for events (Controller method that returns JSON)
                events: "{{ route('tutor.calendar.events') }}",

                // EVENT: Click on empty slot (Create new session)
                dateClick: function(info) {
                    // Send custom event with date info to open modal (handled in a parent component)
                    window.dispatchEvent(new CustomEvent('open-session-modal', { 
                        detail: { 
                            date: info.dateStr.split('T')[0], // Blank date part
                            time: info.dateStr.split('T')[1] || '09:00:00' // Time
                        } 
                    }));
                },
                eventClick: function(info) {
                    const s = info.event;
                    const props = s.extendedProps || s._def.extendedProps;
                    // Parse time from startStr (which FullCalendar formats in the configured
                    // timezone) instead of s.start.getHours() (which uses browser-local time).
                    // This prevents stored times from drifting when browser tz ≠ tutor tz.
                    const startTimeStr = (s.startStr.split('T')[1] || '09:00:00');
                    const h24 = parseInt(startTimeStr.substring(0, 2), 10);
                    const mins = startTimeStr.substring(3, 5);
                    const ampm = h24 >= 12 ? 'PM' : 'AM';
                    const h12 = ((h24 % 12) || 12).toString().padStart(2, '0');

                    window.dispatchEvent(new CustomEvent('open-modal', {
                        detail: { 
                            isEdit: true,
                            sessionId: s.id,
                            title: 'Edit Session',
                            type: '{{ auth()->user()->role }}',
                            isRecurring: props.isRecurring,
                            isInitial: props.isInitial,
                            recurringWeekly: props.isRecurringWeekly,
                            studentId: props.studentId,
                            tutorId: props.tutorId,
                            subject: props.subject,
                            duration: props.duration,
                            location: props.location,
                            date: s.startStr.split('T')[0],
                            time_h: h12,
                            time_m: mins,
                            time_ampm: ampm,
                            studentTimezone: (window.studentTimezoneMap || {})[props.studentId] || ''
                        }
                    }));
                }
            });

            window.calendar.render();
        });
    </script>
    @endpush
</x-app-layout>

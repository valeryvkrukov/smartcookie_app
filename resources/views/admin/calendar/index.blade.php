<x-app-layout>
    <x-slot name="header_title">
        Calendar
    </x-slot>

    <div class="bg-white p-6 shadow-sm border border-slate-200 rounded-2xl">
        <!-- Filter in Glassmorphism style -->
        <div class="mb-8 p-6 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Filter by Tutor</label>
                <select id="tutor-filter" class="border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-2 font-bold text-slate-800 transition-colors min-w-[200px]">
                    <option value="">All Tutors</option>
                    @foreach($tutors as $t)
                        <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="calendar" style="min-height: 700px;"></div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const filterEl = document.getElementById('tutor-filter');

            // student_id -> timezone map, used by the modal to show the offset badge
            window.studentTimezoneMap = @json($students->pluck('time_zone', 'id'));

            const calendar = new FullCalendar.Calendar(calendarEl, {
                plugins: [
                    FullCalendar.timeGridPlugin,
                    FullCalendar.dayGridPlugin,
                    FullCalendar.interactionPlugin
                ],
                initialView: 'timeGridWeek',
                timeZone: '{{ auth()->user()->time_zone ?? "local" }}',
                selectable: true,
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                // Quick Add by click on date
                dateClick: function(info) {
                    // Call to `Create` modal (type 'admin')
                    window.dispatchEvent(new CustomEvent('open-modal', { 
                        detail: { 
                            date: info.dateStr, 
                            type: 'admin', 
                            title: 'Schedule New Session' 
                        } 
                    }));
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();

                    const s = info.event;
                    const props =  s.extendedProps || s._def.extendedProps;
                    const hours = s.start.getHours();
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    const h12 = (hours % 12 || 12).toString().padStart(2, '0');
                    const mins = s.start.getMinutes().toString().padStart(2, '0');

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
                },
                events: {
                    url: "{{ route('admin.calendar.events') }}",
                    extraParams: function() { 
                        // Calls on refetchEvents
                        return {
                            tutor_id: document.getElementById('tutor-filter').value
                        };
                    }
                },
                lazyFetching: false,
                allDaySlot: false,
                slotMinTime: '00:00:00',
                slotMaxTime: '23:59:59',
                firstDay: 0,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                }
            });
            calendar.render();

            window.calendar = calendar;

            filterEl.addEventListener('change', function() {
                window.calendar.refetchEvents();
            });
        });
    </script>
    @endpush
</x-app-layout>

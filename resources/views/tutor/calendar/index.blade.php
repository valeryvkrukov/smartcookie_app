<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Dashboard >
        </a>
        My Teaching Schedule
    </x-slot>

    <div class="bg-white p-6 rounded-[2.5rem] border border-slate-200 shadow-sm">
        <x-calendar-legend />
        <div id="cal-date-title" class="text-center font-bold text-slate-800 mb-2 sm:hidden" style="font-size:1.05rem;"></div>
        <div id="calendar" class="min-h-[700px]"></div>
    </div>

    {{-- ── Desktop hover tooltip ──────────────────────────────────── --}}
    <div id="cal-tooltip"
         class="pointer-events-none fixed z-50 hidden max-w-xs rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/50 text-sm"
         style="transition: opacity .12s ease;">
        <div id="cal-tooltip-inner" class="p-4 space-y-2"></div>
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

                initialView: window.innerWidth < 640 ? 'timeGridDay' : 'timeGridWeek',
                headerToolbar: window.innerWidth < 640 ? false : {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                footerToolbar: window.innerWidth < 640 ? {
                    left: 'prev,next',
                    right: 'today'
                } : false,
                datesSet: function(info) {
                    var el = document.getElementById('cal-date-title');
                    if (el) el.textContent = info.view.title;
                },

                // Data source for events (Controller method that returns JSON)
                events: "{{ route('tutor.calendar.events') }}",

                // ── Desktop hover tooltip ────────────────────────────
                eventMouseEnter: function(info) {
                    if (window.innerWidth < 1024) return;
                    const p = info.event.extendedProps;
                    const tip = document.getElementById('cal-tooltip');
                    const inner = document.getElementById('cal-tooltip-inner');

                    const statusColor = {
                        'Scheduled': 'text-indigo-600',
                        'Completed': 'text-emerald-600',
                        'Cancelled': 'text-slate-400',
                    }[p.status] ?? 'text-slate-600';

                    inner.innerHTML = `
                        <div class="flex items-center justify-between gap-3 pb-2 border-b border-slate-100">
                            <span class="font-bold text-slate-800 truncate">${p.subject}</span>
                            <span class="shrink-0 font-semibold ${statusColor} text-xs">${p.status}</span>
                        </div>
                        <div class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1 text-slate-600">
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Student</span>
                            <span class="text-xs font-medium">${p.studentName}</span>
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Time</span>
                            <span class="text-xs">${p.time_h}:${p.time_m} ${p.time_ampm} &bull; ${p.duration}h</span>
                            ${p.location ? `<span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Location</span><span class="text-xs">${p.location}</span>` : ''}
                        </div>
                    `;

                    tip.classList.remove('hidden');
                    tip.style.opacity = '0';

                    const rect = info.el.getBoundingClientRect();
                    const vw = window.innerWidth, vh = window.innerHeight;
                    tip.style.left = '0px'; tip.style.top = '0px';
                    tip.style.opacity = '1';
                    const tw = tip.offsetWidth, th = tip.offsetHeight;

                    let left = rect.right + 10;
                    let top  = rect.top;
                    if (left + tw > vw - 12) left = rect.left - tw - 10;
                    if (top  + th > vh - 12) top  = vh - th - 12;
                    tip.style.left = left + 'px';
                    tip.style.top  = top  + 'px';

                    tip._moveHandler = function(e) {
                        let x = e.clientX + 16, y = e.clientY + 12;
                        if (x + tw > vw - 8) x = e.clientX - tw - 16;
                        if (y + th > vh - 8) y = e.clientY - th - 12;
                        tip.style.left = x + 'px';
                        tip.style.top  = y + 'px';
                    };
                    document.addEventListener('mousemove', tip._moveHandler);
                },
                eventMouseLeave: function() {
                    const tip = document.getElementById('cal-tooltip');
                    tip.classList.add('hidden');
                    if (tip._moveHandler) {
                        document.removeEventListener('mousemove', tip._moveHandler);
                        tip._moveHandler = null;
                    }
                },

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
                            time_h: props.time_h,
                            time_m: props.time_m,
                            time_ampm: props.time_ampm,
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

<x-app-layout>
    <x-slot name="header_title">
        Calendar
    </x-slot>

    <div class="bg-white p-6 shadow-sm border border-slate-200 rounded-2xl">
        <!-- Filter in Glassmorphism style -->
        <div class="mb-8 p-6 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-6 gap-2 sm:gap-0">
                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Filter by Tutor</label>
                <select id="tutor-filter" class="border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-2 font-bold text-slate-800 transition-colors min-w-[200px]">
                    <option value="">All Tutors</option>
                    @foreach($tutors as $t)
                        <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-calendar-legend :show-no-credits="true" />
        <div id="cal-date-title" class="text-center font-bold text-slate-800 mb-2 sm:hidden" style="font-size:1.05rem;"></div>
        <div id="calendar" style="min-height: 700px;"></div>
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

                // ── Desktop hover tooltip ────────────────────────────
                eventMouseEnter: function(info) {
                    if (window.innerWidth < 1024) return; // desktop only
                    const p = info.event.extendedProps;
                    const tip = document.getElementById('cal-tooltip');
                    const inner = document.getElementById('cal-tooltip-inner');

                    const statusColor = {
                        'Scheduled':  'text-indigo-600',
                        'Completed':  'text-emerald-600',
                        'Cancelled':  'text-slate-400',
                    }[p.status] ?? 'text-slate-600';

                    inner.innerHTML = `
                        <div class="flex items-center justify-between gap-3 pb-2 border-b border-slate-100">
                            <span class="font-bold text-slate-800 truncate">${p.subject}</span>
                            <span class="shrink-0 font-semibold ${statusColor} text-xs">${p.status}</span>
                        </div>
                        <div class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1 text-slate-600">
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Tutor</span>
                            <span class="text-xs font-medium">${p.tutorName}</span>
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Student</span>
                            <span class="text-xs font-medium">${p.studentName}</span>
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Time</span>
                            <span class="text-xs">${p.time_h}:${p.time_m} ${p.time_ampm} &bull; ${p.duration}h</span>
                            ${p.location ? `<span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Location</span><span class="text-xs">${p.location}</span>` : ''}
                        </div>
                    `;

                    tip.classList.remove('hidden');
                    tip.style.opacity = '0';

                    // Position near the event element
                    const rect = info.el.getBoundingClientRect();
                    const vw = window.innerWidth, vh = window.innerHeight;
                    tip.style.left = '0px'; tip.style.top = '0px'; // reset to measure
                    tip.style.opacity = '1';
                    const tw = tip.offsetWidth, th = tip.offsetHeight;

                    let left = rect.right + 10;
                    let top  = rect.top;
                    if (left + tw > vw - 12) left = rect.left - tw - 10;
                    if (top  + th > vh - 12) top  = vh - th - 12;
                    tip.style.left = left + 'px';
                    tip.style.top  = top  + 'px';

                    // Follow mouse for fine-tuning
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
                            sessionStatus: props.status,
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
                firstDay: 0,
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
            });
            calendar.render();

            window.calendar = calendar;

            filterEl.addEventListener('change', function() {
                window.calendar.refetchEvents();
            });

            // ── Auto-refresh: re-fetch events every 2 minutes so credit colour
            // changes (e.g. after a top-up) and session deletions made elsewhere
            // are reflected without a full page reload.
            setInterval(function() {
                window.calendar.refetchEvents();
            }, 120000);
        });
    </script>
    @endpush
</x-app-layout>

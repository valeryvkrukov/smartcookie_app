<x-app-layout>
    <x-slot name="header_title">
        Calendar
    </x-slot>

    <div class="bg-white p-6 shadow-sm border border-slate-200 rounded-2xl">
        <!-- Filter in Glassmorphism style -->
        <div class="mb-8 p-6 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 flex items-center justify-between">
            <div id="filter-tutor-wrap" class="flex flex-col sm:flex-row sm:items-center sm:space-x-6 gap-2 sm:gap-0">
                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Tutor Filter</label>
                <select id="tutor-filter" class="border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-2 font-bold text-slate-800 transition-colors min-w-[200px]">
                    <option value="">All Tutors</option>
                    @foreach($tutors as $t)
                        <option value="{{ $t->id }}">{{ $t->full_name }}{{ $t->role === 'admin' ? ' ★' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div id="filter-student-wrap" class="hidden flex-col sm:flex-row sm:items-center sm:space-x-6 gap-2 sm:gap-0">
                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Student Filter</label>
                <select id="student-filter" class="border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-2 font-bold text-slate-800 transition-colors min-w-[200px]">
                    <option value="">All Students</option>
                    @foreach($myStudents as $s)
                        <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                    @endforeach
                </select>
            </div>

            @if(auth()->user()->can_tutor)
            <div id="schedule-toggle" class="flex bg-slate-100 rounded-2xl p-1 gap-1">
                <button type="button" id="toggle-mine"
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 transition-all duration-200">
                    My Schedule
                </button>
                <button type="button" id="toggle-all"
                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] bg-white text-slate-800 shadow-sm transition-all duration-200">
                    All Schedules
                </button>
            </div>
            @endif
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

            // ── Tooltip TZ helpers ───────────────────────────────────
            function getTzOffsetMin(tz, date) {
                const fmt = new Intl.DateTimeFormat('en-US', {
                    timeZone: tz, year: 'numeric', month: 'numeric', day: 'numeric',
                    hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: false
                });
                const pts = Object.fromEntries(fmt.formatToParts(date).map(x => [x.type, x.value]));
                const h = +pts.hour === 24 ? 0 : +pts.hour;
                return (Date.UTC(+pts.year, +pts.month - 1, +pts.day, h, +pts.minute, +pts.second) - date.getTime()) / 60000;
            }
            function fmtTzRow(label, tz, viewerTz, date) {
                if (!tz || tz === viewerTz || !date) return '';
                const localTime = date.toLocaleString('en-US', { timeZone: tz, hour: 'numeric', minute: '2-digit', hour12: true });
                const tzAbbr = date.toLocaleTimeString('en-US', { timeZone: tz, timeZoneName: 'short' }).replace(/^.*\s/, '');
                const diffMins = getTzOffsetMin(tz, date) - getTzOffsetMin(viewerTz, date);
                const sign = diffMins >= 0 ? '+' : '\u2212';
                const absMins = Math.abs(diffMins);
                const dh = Math.floor(absMins / 60), dm = absMins % 60;
                const diffStr = (dh && dm) ? sign+dh+'h\u202f'+dm+'m' : dm ? sign+dm+'m' : sign+dh+'h';
                return '<span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">'+label+'</span>'
                     + '<span class="text-xs text-slate-500">'+localTime+' <span class="text-slate-400">'+tzAbbr+'</span>'
                     + ' <span class="text-slate-300 text-[11px]">('+diffStr+')</span></span>';
            }

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
                            <span class="text-xs">${(function(){
                                const iso = info.event.startStr;
                                const tp = iso.split('T')[1];
                                const hh = parseInt(tp.split(':')[0]), mm = tp.split(':')[1];
                                const viewerTimeStr = (hh % 12 || 12) + ':' + mm + ' ' + (hh >= 12 ? 'PM' : 'AM');
                                const dur = parseInt(p.duration);
                                return viewerTimeStr + ' &bull; ' + (dur < 60 ? dur+'m' : dur/60+'h');
                            })()}</span>
                            ${(function(){
                                const viewerTz = '{{ auth()->user()->time_zone ?? '' }}';
                                const studentTz = (window.studentTimezoneMap || {})[p.studentId] || '';
                                const tutorTz   = p.tutorTimezone || '';
                                return fmtTzRow('Stu.\u00a0TZ', studentTz, viewerTz, info.event.start)
                                     + fmtTzRow('Tutor\u00a0TZ', tutorTz, viewerTz, info.event.start);
                            })()}
                            ${p.location ? `<span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Location</span><span class="text-xs">${p.location}</span>` : ''}
                            ${p.status === 'Cancelled' ? `<span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Reason</span><span class="text-xs italic text-slate-500">${p.cancelReason || 'No cancellation reason provided'}</span>` : ''}
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
                        const isMine = @if(auth()->user()->can_tutor) document.getElementById('toggle-mine').classList.contains('bg-white') @else false @endif;
                        return isMine
                            ? { tutor_id: '{{ auth()->id() }}', student_id: document.getElementById('student-filter')?.value ?? '' }
                            : { tutor_id: document.getElementById('tutor-filter').value, student_id: '' };
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
                @if(auth()->user()->can_tutor)
                syncToggle();
                @endif
                window.calendar.refetchEvents();
            });

            @if(auth()->user()->can_tutor)
            const adminId    = '{{ auth()->id() }}';
            const toggleMine = document.getElementById('toggle-mine');
            const toggleAll  = document.getElementById('toggle-all');

            const tutorFilterWrap   = document.getElementById('filter-tutor-wrap');
            const studentFilterWrap = document.getElementById('filter-student-wrap');
            const studentFilterEl   = document.getElementById('student-filter');

            studentFilterEl.addEventListener('change', function() {
                window.calendar.refetchEvents();
            });

            function syncToggle() {
                const isMine = filterEl.value === adminId;
                toggleMine.classList.toggle('bg-white',       isMine);
                toggleMine.classList.toggle('shadow-sm',      isMine);
                toggleMine.classList.toggle('text-slate-800', isMine);
                toggleMine.classList.toggle('text-slate-500', !isMine);
                toggleAll.classList.toggle('bg-white',        !isMine);
                toggleAll.classList.toggle('shadow-sm',       !isMine);
                toggleAll.classList.toggle('text-slate-800',  !isMine);
                toggleAll.classList.toggle('text-slate-500',  isMine);
                tutorFilterWrap.classList.toggle('hidden',  isMine);
                studentFilterWrap.classList.toggle('hidden',  !isMine);
                studentFilterWrap.classList.toggle('flex',     isMine);
            }

            toggleMine.addEventListener('click', function() {
                filterEl.value = adminId;
                syncToggle();
                window.calendar.refetchEvents();
            });

            toggleAll.addEventListener('click', function() {
                filterEl.value = '';
                syncToggle();
                window.calendar.refetchEvents();
            });

            syncToggle();
            @endif

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

<x-app-layout>
    <x-slot name="header_title">Family Schedule</x-slot>

    <!-- Student Filter (Apple-style Segmented Control) -->
    <div class="mb-10 flex justify-center">
        <div class="inline-flex bg-white p-1.5 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-x-auto max-w-full">
            <button onclick="filterByStudent('')" 
                    id="btn-all"
                    class="student-filter-btn px-6 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest transition-all bg-[#212120] text-white shadow-lg">
                All Kids
            </button>
            @foreach($students as $s)
                <button onclick="filterByStudent('{{ $s->id }}')" 
                        id="btn-{{ $s->id }}"
                        class="student-filter-btn px-6 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                    {{ $s->first_name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Next 2 Sessions -->
    <div class="mb-8">
        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Upcoming Sessions</h3>
        @if($nextSessions->isEmpty())
            <div class="px-6 py-5 bg-white border border-slate-100 rounded-3xl text-[11px] font-bold text-slate-400 italic shadow-sm">
                No sessions scheduled
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($nextSessions as $ns)
                    <div class="next-session-card px-6 py-5 bg-white border border-slate-100 rounded-3xl shadow-sm hover:shadow-md transition-shadow cursor-pointer space-y-1"
                         data-session-id="{{ $ns->id }}"
                         data-subject="{{ $ns->subject }}"
                         data-tutor="{{ $ns->tutor?->full_name ?? 'TBD' }}"
                         data-student="{{ $ns->student?->full_name ?? '' }}"
                         data-location="{{ $ns->location ?? '' }}"
                         data-start="{{ \Carbon\Carbon::parse($ns->start_time)->format('g:i A') }} · {{ $ns->date->format('D, M j, Y') }}"
                         data-iso="{{ $ns->date->format('Y-m-d') }}T{{ \Carbon\Carbon::parse($ns->start_time)->format('H:i:s') }}"
                         data-duration="{{ $ns->duration }}"
                         data-status="{{ $ns->status }}"
                         data-recurring="{{ $ns->recurring_id ? '1' : '0' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">{{ $ns->date->format('D, M j') }}</span>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ \Carbon\Carbon::parse($ns->start_time)->format('g:i A') }}</span>
                        </div>
                        <p class="font-black text-slate-900 text-sm">{{ $ns->subject }}</p>
                        <p class="text-xs text-slate-500">{{ $ns->student?->full_name }} · {{ $ns->tutor?->full_name ?? 'TBD' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Calendar container -->
    <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl p-8 overflow-hidden">
        <x-calendar-legend />
        <div id="calendar"></div>
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
            // Next-session card clicks
            document.querySelectorAll('.next-session-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const d = card.dataset;
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: {
                        type: 'session-info',
                        title: 'Session Details',
                        sessionId: d.sessionId,
                        subject: d.subject,
                        tutorName: d.tutor,
                        studentName: d.student,
                        location: d.location,
                        startTime: d.start,
                        duration: d.duration,
                        sessionStatus: d.status,
                        canCancel: d.status === 'Scheduled' && ((new Date(d.iso) - Date.now()) / (1000 * 60 * 60)) > 24,
                        isRecurring: d.recurring === '1',
                        insufficientCredits: false,
                    }}));
                });
            });

            const calendarEl = document.getElementById('calendar');
            
            // Read initial student_id from URL if present (for deep linking)
            const urlParams = new URLSearchParams(window.location.search);
            const initialStudentId = urlParams.get('student_id') || '';

            if (initialStudentId) {
                window.currentStudentId = initialStudentId;
            }

            window.calendar = new FullCalendar.Calendar(calendarEl, {
                plugins: [ 
                    FullCalendar.dayGridPlugin, 
                    FullCalendar.timeGridPlugin, 
                    FullCalendar.interactionPlugin 
                ],
                initialView: 'timeGridWeek',
                timeZone: '{{ auth()->user()->time_zone ?? "local" }}',
                lazyFetching: false,
                firstDay: 0,
                allDaySlot: false,
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                initialView: window.innerWidth < 640 ? 'timeGridDay' : 'timeGridWeek',
                headerToolbar: window.innerWidth < 640 ? {
                    left: 'prev,next',
                    center: 'title',
                    right: 'today'
                } : {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                footerToolbar: window.innerWidth < 640 ? {
                    center: 'dayGridMonth,timeGridWeek,timeGridDay'
                } : false,
                events: {
                    url: "{{ route('customer.calendar.events') }}",
                    extraParams: function() {
                        return { student_id: window.currentStudentId || initialStudentId };
                    }
                },

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

                    const startDate = new Date(info.event.startStr);
                    const timeStr = startDate.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

                    const creditBadge = p.insufficientCredits
                        ? '<span class="inline-block rounded-full bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5">No Credits</span>'
                        : '';

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
                            <span class="text-xs">${timeStr} &bull; ${p.duration}h</span>
                            ${p.location ? `<span class="text-slate-400 text-xs font-semibold uppercase tracking-wide">Location</span><span class="text-xs">${p.location}</span>` : ''}
                        </div>
                        ${creditBadge ? `<div class="pt-1">${creditBadge}</div>` : ''}
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

                eventClick: function(info) {
                    const sessionStart = new Date(info.event.startStr);
                    const hoursUntil = (sessionStart - new Date()) / (1000 * 60 * 60);
                    const status = info.event.extendedProps.status || '';
                    const canCancel = status === 'Scheduled' && hoursUntil > 24;

                    const formattedStart = sessionStart.toLocaleString('en-US', {
                        weekday: 'short', month: 'short', day: 'numeric',
                        hour: 'numeric', minute: '2-digit'
                    });

                    window.dispatchEvent(new CustomEvent('open-modal', { 
                        detail: { 
                            type: 'session-info',
                            title: 'Session Details',
                            sessionId: info.event.id,
                            subject: info.event.extendedProps.subject,
                            tutorName: info.event.extendedProps.tutorName,
                            studentName: info.event.extendedProps.studentName,
                            location: info.event.extendedProps.location,
                            date: info.event.startStr,
                            startTime: formattedStart,
                            duration: info.event.extendedProps.duration,
                            sessionStatus: status,
                            canCancel: canCancel,
                            isRecurring: !!info.event.extendedProps.recurringId,
                            insufficientCredits: !!info.event.extendedProps.insufficientCredits,
                        } 
                    }));
                }
            });

            window.calendar.render();

            // If we came with ?student_id=... in URL, apply that filter immediately
            if (initialStudentId) filterByStudent(initialStudentId);
        });

        function filterByStudent(id) {
            window.currentStudentId = id;
            window.calendar.refetchEvents();

            // UI: Change active button style
            document.querySelectorAll('.student-filter-btn').forEach(btn => {
                btn.classList.remove('bg-[#212120]', 'text-white', 'shadow-lg');
                btn.classList.add('text-slate-400');
            });
            const activeBtn = document.getElementById(id ? 'btn-' + id : 'btn-all');
            activeBtn.classList.add('bg-[#212120]', 'text-white', 'shadow-lg');
            activeBtn.classList.remove('text-slate-400');
        }
    </script>
    @endpush
</x-app-layout>

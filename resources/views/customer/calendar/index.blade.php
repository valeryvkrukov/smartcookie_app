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
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: {
                    url: "{{ route('customer.calendar.events') }}",
                    extraParams: function() {
                        return { student_id: window.currentStudentId || initialStudentId };
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

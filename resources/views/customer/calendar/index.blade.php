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

    <!-- Calendar container -->
    <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl p-8 overflow-hidden">
        <div id="calendar"></div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                events: {
                    url: "{{ route('customer.calendar.events') }}",
                    extraParams: function() {
                        return { student_id: window.currentStudentId || initialStudentId };
                    }
                },
                eventClick: function(info) {
                    // Customers can only view session details, not edit
                    window.dispatchEvent(new CustomEvent('open-modal', { 
                        detail: { 
                            type: 'session-info', // Новая модалка "Просмотр"
                            title: 'Session Details',
                            subject: info.event.extendedProps.subject,
                            tutorName: info.event.title,
                            date: info.event.startStr,
                            duration: info.event.extendedProps.duration
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

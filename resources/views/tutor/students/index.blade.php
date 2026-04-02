<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Dashboard >
        </a>
        Assigned Students
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($students as $student)
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col">
                
                <!-- Header: Name and Balance -->
                <div class="p-6 border-b border-slate-50 flex justify-between items-start bg-slate-50/30">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-[#212120] text-white rounded-2xl flex items-center justify-center font-bold text-xl shadow-sm">
                            {{ substr($student->first_name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-lg leading-tight">{{ $student->full_name }}</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $student->tutoring_subject ?? 'General' }}</p>
                        </div>
                    </div>
                    
                    <!-- Credit Balance (Important for Tutor) -->
                    <div class="flex flex-col items-end">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Credits</span>
                        <span class="text-sm font-black {{ ($student->parent->credit->credit_balance ?? 0) > 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                            {{ $student->parent->credit->credit_balance ?? 0 }}
                        </span>
                    </div>
                </div>
                
                <!-- Body: Academic Info -->
                <div class="p-6 space-y-4 flex-1">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Grade</p>
                            <p class="text-xs font-bold text-slate-700">{{ $student->student_grade ?? '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">School</p>
                            <p class="text-xs font-bold text-slate-700 truncate">{{ $student->student_school ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="pt-1">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Parent / Client</p>
                        <div class="flex items-center text-xs font-bold text-slate-700">
                            <i class="ti-user mr-2 text-slate-300"></i>
                            {{ $student->parent->full_name ?? 'Not Assigned' }}
                        </div>
                    </div>
                    <!-- Goal -->
                    <div class="pt-1 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Learning Goal</p>
                        <p class="text-xs text-slate-600 italic leading-relaxed">
                            {{ $student->tutoring_goals ? '"' . $student->tutoring_goals . '"' : 'No specific goals set yet.' }}
                        </p>
                    </div>
                </div>

                <!-- Footer: Quick Actions (Mobile-First) -->
                <div class="px-6 py-4 bg-slate-50 flex items-center justify-between border-t border-slate-100">
                    <div class="flex items-center space-x-3">
                        <!-- Call Parent -->
                        @if($student->parent->phone)
                        <a href="tel:{{ $student->parent->phone }}" class="p-2 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 hover:text-indigo-600 shadow-sm transition-colors">
                            <i class="ti-mobile text-sm"></i>
                        </a>
                        @endif
                        <!-- Write to Parent -->
                        <a href="mailto:{{ $student->parent->email }}" class="p-2 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 hover:text-indigo-600 shadow-sm transition-colors">
                            <i class="ti-email text-sm"></i>
                        </a>
                    </div>

                    <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-modal', {
                            detail: {
                                type: 'tutor-schedule-session', 
                                studentId: '{{ $student->id }}',
                                tutorId: '{{ auth()->id() }}',
                                studentTimezone: '{{ $student->time_zone ?? 'UTC' }}',
                                title: 'Schedule Session for {{ $student->full_name }}'
                            }
                        }))"
                        class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">
                        Schedule Session
                    </button>
                    
                    <!--a href="{{ route('tutor.sessions.create', ['student_id' => $student->id]) }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">
                        Schedule Session
                    </a-->
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center bg-slate-50 rounded-[2rem] border-2 border-dashed border-slate-200">
                <i class="ti-id-badge text-4xl text-slate-300 mb-4 inline-block"></i>
                <p class="text-slate-500 font-bold">No students assigned to you yet.</p>
                <p class="text-xs text-slate-400 mt-1">Please contact Sophie to update your roster.</p>
            </div>
        @endforelse
    </div>

</x-app-layout>

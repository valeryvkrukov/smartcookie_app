<x-app-layout>
    <x-slot name="header_title">My Students</x-slot>

    <div class="max-w-6xl mx-auto pb-20">
        
        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Family Profiles</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Manage your children's tutoring profiles</p>
            </div>
            <!-- According to the requirements, parents can add students -->
            <button type="button" 
                onclick="window.dispatchEvent(new CustomEvent('open-modal', { 
                    detail: { 
                        type: 'add-student',
                        isEdit: false,
                        date: '',
                        title: 'Register New Student' 
                    } 
                }))"
                class="px-8 py-4 bg-[#212120] text-white rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-black shadow-xl shadow-slate-200 transition-all active:scale-95">
                + Add Student
            </button>
        </div>

        <!-- Students Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            @forelse($students as $student)
            @php $isSelf = isset($selfStudentId) && $student->id === $selfStudentId; @endphp
            <div class="bg-white rounded-[3.5rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/40 flex flex-col group hover:-translate-y-1 transition-all duration-500 relative overflow-hidden">
                
                <!-- Decor on the background -->
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-slate-50 rounded-full opacity-50 group-hover:scale-110 transition-transform duration-700"></div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center space-x-6">
                            <div class="w-16 h-16 {{ $isSelf ? 'bg-emerald-600' : 'bg-indigo-600' }} text-white rounded-[1.5rem] flex items-center justify-center text-2xl font-black shadow-lg {{ $isSelf ? 'shadow-emerald-100' : 'shadow-indigo-100' }}">
                                {{ substr($student->first_name, 0, 1) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ $student->full_name }}</h3>
                                    @if($isSelf)
                                        <span class="text-[8px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full">You</span>
                                    @endif
                                </div>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $student->student_grade ?? 'No Grade Set' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 absolute right-4">
                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-modal', { 
                                    detail: { 
                                        type: 'edit-student',
                                        isEdit: true,
                                        isSelfProfile: {{ $isSelf ? 'true' : 'false' }},
                                        studentId: {{ $student->id }}, 
                                        title: {{ json_encode('Edit ' . $student->first_name . "'s Profile") }},
                                        firstName: {{ json_encode($student->first_name) }},
                                        lastName: {{ json_encode($student->last_name) }},
                                        grade: {{ json_encode($student->student_grade ?? '') }},
                                        blurb: {{ json_encode($student->blurb ?? '') }},
                                        studentAddress: {{ json_encode($student->address ?? '') }},
                                        studentPhone: {{ json_encode($student->phone ?? '') }},
                                        studentEmail: {{ json_encode($isSelf || str_ends_with($student->email ?? '', '@smartcookie.local') ? '' : ($student->email ?? '')) }},
                                    } 
                                }))"
                                class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center hover:bg-[#212120] hover:text-white transition-all">
                                <i class="ti-pencil text-xs"></i>
                            </button>

                            @if(!$isSelf)
                            <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('confirm-user-delete', { 
                                    detail: { 
                                        name: {{ json_encode($student->full_name) }}, 
                                        formId: 'delete-student-{{ $student->id }}' 
                                    } 
                                }))"
                                class="w-10 h-10 bg-rose-50 text-rose-400 rounded-xl flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all">
                                <i class="ti-trash text-xs"></i>
                            </button>
                            
                            <form id="delete-student-{{ $student->id }}" action="{{ route('customer.students.destroy', $student->id) }}" method="POST" class="hidden">
                                @csrf @method('DELETE')
                            </form>
                            @endif
                        </div>
                    </div>

                    <!-- Info about Tutor and Subjects -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Assigned Tutor</p>
                            <p class="text-xs font-black text-slate-900">{{ $student->assignedTutors->first()->full_name ?? 'Not Assigned' }}</p>
                        </div>
                        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Active Subjects</p>
                            <div class="flex flex-wrap gap-1">
                                @forelse($student->subjectRates as $sr)
                                    <span class="text-[9px] font-black text-indigo-600 uppercase">{{ $sr->subject }}@if(!$loop->last),@endif</span>
                                @empty
                                    <span class="text-[9px] font-black text-slate-400">None</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Buttons (Modern UX) -->
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('customer.calendar.index', ['student_id' => $student->id]) }}" 
                           class="flex-1 py-4 bg-[#212120] text-white rounded-2xl text-[9px] font-black uppercase tracking-widest text-center hover:bg-black transition-all shadow-lg">
                            View Calendar
                        </a>
                        <a href="{{ route('customer.credits.index') }}" 
                           class="px-6 py-4 bg-emerald-50 text-emerald-600 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-emerald-100 transition-all">
                            Buy Credits
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full py-20 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.3em]">No students found. Add your first child above.</p>
            </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

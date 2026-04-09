<x-app-layout>
    <x-slot name="header_title">{{ auth()->user()->is_self_student ? 'My Student Profile' : 'Family Profiles' }}</x-slot>

    <div class="max-w-6xl mx-auto pb-20">

        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center space-x-3 text-emerald-700">
                <i class="ti-check mr-2"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center space-x-3 text-rose-600">
                <i class="ti-alert mr-2"></i> {{ session('error') }}
            </div>
        @endif

        <!-- Header Actions -->
        <div class="flex justify-between items-start mb-12 gap-4 flex-wrap">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                    {{ auth()->user()->is_self_student ? 'My Profile and Students' : 'Family Profiles' }}
                </h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">
                    {{ auth()->user()->is_self_student ? 'You are enrolled as a student and can also manage children' : 'Manage your children\'s tutoring profiles' }}
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                {{-- Self-student toggle --}}
                <form id="toggle-self-student-form" method="POST" action="{{ route('customer.students.toggle-self-student') }}">
                    @csrf
                    <button type="button"
                        @click="$dispatch('confirm-toggle-self-student')"
                        class="px-6 py-3.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all border
                            {{ auth()->user()->is_self_student
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                                : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100' }}">
                        {{ auth()->user()->is_self_student ? '↩ Switch to Parent Mode' : '⇄ I am the Student' }}
                    </button>
                </form>

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
                                    <h3 class="text-xl font-black {{ $student->is_inactive ? 'text-slate-400' : 'text-slate-900' }} tracking-tight">{{ $student->full_name }}</h3>
                                    @if($isSelf)
                                        <span class="text-[8px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full">You</span>
                                    @elseif($student->is_inactive)
                                        <span class="text-[8px] font-black uppercase tracking-widest bg-slate-100 text-slate-400 px-2 py-0.5 rounded-full">Inactive</span>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
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

    {{-- ── Toggle Self-Student confirmation modal ──────────────────────── --}}
    @php $isSelf = auth()->user()->is_self_student; @endphp
    <div x-data="{ open: false }"
         @confirm-toggle-self-student.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         style="display: none !important;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" @click="open = false"></div>
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative z-10 w-full max-w-sm bg-white rounded-[3rem] shadow-2xl border border-slate-100 p-10 text-center">

            <div class="w-20 h-20 {{ $isSelf ? 'bg-amber-50' : 'bg-indigo-50' }} {{ $isSelf ? 'text-amber-500' : 'text-indigo-500' }} rounded-3xl flex items-center justify-center text-3xl mx-auto mb-6 shadow-sm">
                <i class="{{ $isSelf ? 'ti-arrow-left' : 'ti-user' }}"></i>
            </div>

            <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                {{ $isSelf ? 'Switch to Parent Mode?' : 'Switch to Self-Student?' }}
            </h3>
            <p class="mt-4 text-slate-500 leading-relaxed text-sm">
                @if($isSelf)
                    You will be switched back to <strong class="text-slate-700">Parent Mode</strong>.<br>
                    Your own student profile will be hidden, but your children's profiles and all sessions remain unchanged.
                @else
                    You will also appear as <strong class="text-slate-700">a student yourself</strong>.<br>
                    Your children's profiles and all existing sessions remain unchanged.
                @endif
            </p>

            <div class="mt-10 space-y-3">
                <button type="button"
                        @click="open = false; document.getElementById('toggle-self-student-form').submit()"
                        class="w-full py-4 {{ $isSelf ? 'bg-amber-500 hover:bg-amber-600 shadow-amber-100' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-100' }} text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition shadow-lg leading-none">
                    {{ $isSelf ? 'Yes, switch to Parent Mode' : 'Yes, I am the Student' }}
                </button>
                <button type="button" @click="open = false"
                        class="w-full py-4 text-slate-400 text-[10px] font-bold uppercase tracking-widest hover:text-slate-600 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

</x-app-layout>

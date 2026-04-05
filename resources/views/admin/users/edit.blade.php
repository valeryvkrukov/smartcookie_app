<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 transition">
            User Directory >
        </a>
        Edit Profile
    </x-slot>

    <form 
        x-data="{ 
            fname: '{{ $user->first_name }}', 
            lname: '{{ $user->last_name }}' 
        }"
        action="{{ route('admin.users.update', $user->id) }}" 
        method="POST" 
        class="max-w-5xl mx-auto pb-20">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="mb-6 p-4 bg-rose-50 text-rose-600 rounded-2xl text-xs font-bold uppercase border border-rose-100">
                @foreach ($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <input type="hidden" name="role" value="{{ $user->role }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
            
            <!-- LEFT COLUMN: Main Information -->
            <div class="lg:col-span-2 space-y-8 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/40">
                <div class="flex items-center space-x-6 mb-10 pb-10 border-b border-slate-50">
                    <div class="w-20 h-20 bg-[#212120] text-white rounded-[2rem] flex items-center justify-center text-3xl font-black shadow-xl">
                        {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $user->full_name }}</h2>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase rounded-lg tracking-widest">{{ $user->role }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="label-premium">First Name</label>
                        <input x-model="fname" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="input-premium" required>
                    </div>
                    <div class="space-y-2">
                        <label class="label-premium">Last Name</label>
                        <input x-model="lname" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="input-premium" required>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="label-premium">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input-premium" required>
                </div>

                <!-- Block for Tutor (Bio) -->
                @if($user->role === 'tutor')
                <div class="space-y-2 pt-4">
                    <label class="label-premium">Tutor Bio / Description</label>
                    <textarea name="blurb" rows="4" class="w-full bg-slate-50 border-none rounded-3xl p-6 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-[#212120] transition-all">{{ old('blurb', $user->blurb) }}</textarea>
                </div>
                @endif

                <div class="space-y-2">
                    <label class="label-premium">System Role</label>
                    <select name="role" class="input-premium">
                        <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="tutor" {{ $user->role == 'tutor' ? 'selected' : '' }}>Tutor</option>
                        <option value="customer" {{ $user->role == 'customer' ? 'selected' : '' }}>Customer (Parent)</option>
                        <option value="student" {{ $user->role == 'student' ? 'selected' : '' }}>Student</option>
                    </select>
                </div>
                @if($user->role === 'student')
                <!--input type="hidden" name="student_id" :value="studentId"-->
                <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl mt-10">
                    <div class="flex justify-between items-center mb-10">
                        <div>
                            <h3 class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.3em]">Subject Rates</h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Individual pricing for this student</p>
                        </div>
                        <!-- Button for adding new subject rate -->
                        <button type="button" 
                            @click="
                                $dispatch('open-modal', {
                                    type: 'add-subject-rate',
                                    studentId: '{{ $user->id }}',
                                    title: 'Add New Subject Rate'
                                })
                            "
                            class="px-6 py-3 bg-[#212120] text-white rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-black transition-all active:scale-95 shadow-lg shadow-slate-200">
                            + Add Subject
                        </button>
                    </div>

                    <div class="space-y-4">
                        @forelse($user->subjectRates as $sr)
                            <div class="flex items-center justify-between p-6 bg-slate-50 rounded-3xl border border-slate-100 group hover:bg-white hover:shadow-xl hover:shadow-slate-100 transition-all duration-500">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-white rounded-2xl flex items-center justify-center text-slate-400 shadow-sm group-hover:text-indigo-600 transition-colors">
                                        <i class="ti-bookmark-alt"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black uppercase text-slate-900 tracking-tight">{{ $sr->subject }}</p>
                                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Fixed hourly rate</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-6">
                                    <span class="text-sm font-black text-emerald-600">${{ number_format($sr->rate, 2) }}</span>
                                    <!-- Button for deleting the subject rate (using our global delete modal) -->
                                    <button type="button" 
                                        onclick="window.dispatchEvent(new CustomEvent('confirm-delete', { detail: {
                                            name: '{{ $sr->subject }} rate', 
                                            formId: 'delete-rate-{{ $sr->id }}'
                                        }}))"
                                        class="text-slate-300 hover:text-rose-500 transition-colors">
                                        <i class="ti-trash"></i>
                                    </button>
                                    <form id="delete-rate-{{ $sr->id }}" action="{{ route('admin.subject-rates.destroy', $sr->id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 border-2 border-dashed border-slate-100 rounded-[2.5rem]">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">No custom rates set. Using global default.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                @endif

                @if($user->role === 'admin')
                <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-xl mt-6">
                    <h3 class="label-premium mb-6">Teaching Permissions</h3>
                    
                    <label class="flex items-center justify-between cursor-pointer group">
                        <span class="text-xs font-bold text-slate-600 group-hover:text-slate-900 transition-colors">Can provide tutoring?</span>
                        <div class="relative">
                            <input type="checkbox" name="can_tutor" value="1" {{ $user->can_tutor ? 'checked' : '' }} class="peer hidden">
                            <div class="w-12 h-6 bg-slate-200 rounded-full peer-checked:bg-emerald-500 transition-colors duration-300"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-300 peer-checked:translate-x-6 shadow-sm"></div>
                        </div>
                    </label>
                    <p class="text-[9px] text-slate-400 mt-3 leading-relaxed">If enabled, this user will appear in the "Tutor" dropdowns across the system.</p>
                </div>
                @endif

                @if($user->role === 'customer')
                <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-xl mt-6">
                    <h3 class="label-premium mb-6">Account Type</h3>
                    <label class="flex items-center justify-between cursor-pointer group">
                        <div>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-slate-900 transition-colors">Self-Enrolled Student</span>
                            <p class="text-[9px] text-slate-400 mt-1 leading-relaxed max-w-xs">This customer IS also the student in sessions. No separate student account is needed.</p>
                        </div>
                        <div class="relative ml-6 flex-shrink-0">
                            <input type="checkbox" name="is_self_student" value="1" {{ $user->is_self_student ? 'checked' : '' }} class="peer hidden">
                            <div class="w-12 h-6 bg-slate-200 rounded-full peer-checked:bg-indigo-500 transition-colors duration-300"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-300 peer-checked:translate-x-6 shadow-sm"></div>
                        </div>
                    </label>
                </div>

                {{-- ── Manual Payment / Credit Top-up ─────────────────────────── --}}
                @php
                    $rate            = $user->credit?->dollar_cost_per_credit;
                    $pendingAmount   = $user->credit?->pending_payment_amount;
                    $pendingMethod   = $user->credit?->pending_payment_method ?? 'venmo';
                    $pendingCredits  = ($rate && $pendingAmount) ? round($pendingAmount / $rate, 2) : null;
                @endphp
                <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-xl mt-6">
                    <div class="flex items-baseline justify-between mb-6">
                        <h3 class="label-premium">Credit Balance</h3>
                        <p class="text-3xl font-black text-indigo-600">
                            {{ number_format($user->credit?->credit_balance ?? 0, 2) }}
                        </p>
                    </div>

                    <form action="{{ route('admin.users.apply-payment', $user->id) }}" method="POST" class="space-y-4">
                        @csrf

                        {{-- Read-only summary row --}}
                        <div class="grid grid-cols-3 gap-3 p-4 bg-slate-50 rounded-2xl">
                            <div>
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Amount</p>
                                <p class="text-2xl font-black text-slate-800">
                                    {{ $pendingAmount ? '$' . number_format($pendingAmount, 2) : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Credits</p>
                                <p class="text-2xl font-black text-indigo-600">
                                    {{ $pendingCredits ?? '—' }}
                                </p>
                                @if($rate)
                                    <p class="text-[8px] text-slate-400">@ ${{ $rate }}/cr</p>
                                @endif
                            </div>
                            <div>
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Method</p>
                                <p class="text-2xl font-black text-slate-800 capitalize">{{ $pendingMethod }}</p>
                            </div>
                        </div>

                        {{-- Hidden values --}}
                        <input type="hidden" name="total_paid"     value="{{ $pendingAmount ?? 0 }}">
                        <input type="hidden" name="credits"        value="{{ $pendingCredits ?? 0 }}">
                        <input type="hidden" name="payment_method" value="{{ $pendingMethod }}">

                        <div class="space-y-2">
                            <label class="label-premium">Note (optional)</label>
                            <input type="text" name="note" maxlength="255" class="input-premium"
                                   placeholder="e.g. transaction ref or memo">
                        </div>
                        <button type="submit"
                                class="w-full py-3 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all">
                            Confirm Payment &amp; Apply Credits
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <!-- RIGHT COLUMN: Role settings (Contextual Settings) -->
            <div class="space-y-8">
                
                <!-- Rate/Financial (Admin Only) -->
                @if($user->role === 'tutor' || $user->role === 'student')
                <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Financial Settings</h3>
                    
                    @if($user->role === 'tutor')
                        @php $assignments = $user->assignedStudents()->get(); @endphp
                        @if($assignments->isNotEmpty())
                            <div class="space-y-3">
                                <label class="label-premium">Hourly Payout per Student ($)</label>
                                @foreach($assignments as $student)
                                    <div class="flex items-center justify-between gap-4 bg-slate-50 px-4 py-3 rounded-2xl">
                                        <span class="text-xs font-bold text-slate-700 truncate">{{ $student->full_name }}</span>
                                        <input type="number"
                                               name="hourly_payout[{{ $student->id }}]"
                                               value="{{ old('hourly_payout.'.$student->id, $student->pivot->hourly_payout) }}"
                                               step="0.01" min="0"
                                               class="w-28 border-0 border-b-2 border-slate-200 focus:border-[#212120] focus:ring-0 bg-transparent py-1 font-black text-emerald-600 text-right">
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-[9px] text-slate-400 uppercase tracking-widest italic">No students assigned yet.</p>
                        @endif
                    @endif

                    @if($user->role === 'student')
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="label-premium">Assigned Tutor</label>
                                <select name="tutor_id" class="input-premium">
                                    <option value="">None</option>
                                    @foreach($tutors as $t)
                                        <option value="{{ $t->id }}" {{ $user->tutor_id == $t->id ? 'selected' : '' }}>{{ $t->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1 pt-4">
                                <label class="label-premium">Parent / Client</label>
                                <select name="parent_id" class="input-premium">
                                    <option value="">None</option>
                                    @foreach($parents as $p)
                                        <option value="{{ $p->id }}" {{ $user->parent_id == $p->id ? 'selected' : '' }}>{{ $p->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Actions -->
                <div class="bg-[#212120] p-8 rounded-[3rem] shadow-2xl shadow-slate-900/20 text-white">
                    <h3 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-6">Actions</h3>
                    <div class="space-y-4">
                        <button type="submit" class="w-full py-4 bg-white text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 transition-all">
                            Save Changes
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="block w-full py-4 bg-transparent border border-white/20 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-white/5 transition-all">
                            Discard
                        </a>
                    </div>
                </div>

                <!-- Delete Button (Modern Style) -->
                <button type="button" 
                    onclick="window.dispatchEvent(new CustomEvent('confirm-delete', { detail: {
                        name: '{{ $user->full_name }}',
                        formId: 'delete-user-{{ $user->id }}'
                    }}))"
                    class="w-full py-4 bg-rose-50 text-rose-500 rounded-[2rem] text-[10px] font-black uppercase tracking-widest hover:bg-rose-100 transition-all">
                    Delete User Account
                </button>
            </div>
        </div>
    </form>

    {{-- ── Delete form: hidden, submitted programmatically on modal confirmation --}}
    <form id="delete-user-{{ $user->id }}" action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="hidden">
        @csrf @method('DELETE')
    </form>
</x-app-layout>

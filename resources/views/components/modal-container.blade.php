<div x-data="modalData()"
    @open-session-modal.window="openModal($event)"
    @open-modal.window="openModal($event)"
    @set-error.window="errorMessage = ($event.detail && $event.detail.message) ? $event.detail.message : 'Something went wrong'"
    @close-modal.window="open = false"
    x-show="open" 
    x-cloak
    style="display: none !important;"
    class="fixed inset-0 z-50 flex items-center justify-center p-4">
    
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="open = false"></div>

    <div x-show="open" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="relative w-full max-w-lg bg-white p-10 rounded-[3rem] shadow-2xl border border-slate-100 overflow-hidden">
        
        <button @click="open = false" class="absolute top-8 right-8 text-slate-300 hover:text-slate-900 transition-colors">
            <i class="ti-close text-lg"></i>
        </button>

        <div class="text-center mb-10">
            <h2 class="text-2xl font-black text-slate-900 tracking-tight" x-text="title"></h2>
            <template x-if="date">
                <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-[0.2em] mt-1" 
                    x-text="new Date(date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })">
                </p>
            </template>
        </div>

        <!-- Content is loaded dynamically -->
        <div class="space-y-6">
            <template x-if="type === 'tutor'">
                @include('tutor.sessions.partials.quick-form')
            </template>
            <template x-if="type === 'admin'">
                @include('admin.sessions.partials.quick-form')
            </template>
            <template x-if="type === 'add-subject-rate'">
                <form action="{{ route('admin.subject-rates.store') }}" method="POST" class="space-y-8">
                    @csrf
                    <!-- Student ID -->
                    <input type="hidden" name="student_id" :value="studentId">

                    <div class="space-y-2">
                        <label class="label-premium">Subject Name</label>
                        <input type="text" name="subject" class="input-premium" placeholder="e.g. SAT Math" required>
                    </div>

                    <div class="space-y-2">
                        <label class="label-premium">Hourly Rate ($)</label>
                        <input type="number" name="rate" step="0.01" class="input-premium text-xl font-black text-emerald-600" placeholder="50.00" required>
                    </div>

                    <button type="button" @click="submitModalForm($el)" class="btn-primary">Save Subject Rate</button>
                </form>
            </template>
            <template x-if="type === 'zelle-info'">
                <div class="text-center space-y-6">
                    <div class="w-20 h-20 bg-purple-50 text-purple-600 rounded-3xl flex items-center justify-center text-3xl mx-auto">
                        <i class="ti-shift-right"></i>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed px-6">
                        Please send your payment to the following address using the Zelle app:
                    </p>
                    <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Zelle Email</p>
                        <p class="text-lg font-black text-slate-900 tracking-tight">{{ config('payments.zelle.email') }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 mt-3">Payment Note</p>
                        <p class="text-sm text-slate-600">{{ config('payments.zelle.note') }}</p>
                    </div>
                    <p class="text-[9px] font-bold text-rose-500 uppercase tracking-widest italic">
                        * Note: Credits will be added manually after confirmation.
                    </p>
                </div>
            </template>
            <template x-if="type === 'sign-agreement'">
                <form action="{{ route('customer.agreements.sign') }}" method="POST" class="space-y-8">
                    @csrf
                    <input type="hidden" name="request_id" :value="requestId">

                    <div class="text-center space-y-4">
                        <p class="text-xs text-slate-500 leading-relaxed px-4">
                            By signing this document, you acknowledge that you have read and agreed to the terms and conditions outlined in the PDF.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-1">
                            <label class="label-premium">Type Your Full Name</label>
                            <input type="text" name="signed_full_name" class="input-premium font-serif italic text-lg" 
                                placeholder="{{ auth()->user()->full_name }}" required>
                        </div>

                        <div class="space-y-1">
                            <label class="label-premium">Current Date</label>
                            <input type="date" name="signed_date_manual" class="input-premium" 
                                value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>

                    <button type="button" @click="submitModalForm($el)" class="btn-primary">
                        Confirm Signature
                    </button>
                </form>
            </template>
            <template x-if="type === 'send-agreement'">
                <form action="{{ route('admin.agreements.assign') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="user_id" :value="studentId">
                    
                    <div class="space-y-2">
                        <label class="label-premium">Select Document</label>
                        <select name="agreement_id" class="input-premium" required>
                            @foreach(\App\Models\Agreement::all() as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" @click="submitModalForm($el)" class="btn-primary">
                        Send Signature Request
                    </button>
                </form>
            </template>
            <template x-if="type === 'add-student'">
                <form action="{{ route('customer.students.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="label-premium">First Name</label>
                            <input type="text" name="first_name" class="input-premium" required>
                        </div>
                        <div class="space-y-1">
                            <label class="label-premium">Last Name</label>
                            <input type="text" name="last_name" class="input-premium" required>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="label-premium">Grade / School</label>
                        <input type="text" name="student_grade" class="input-premium" placeholder="e.g. 10th Grade">
                    </div>
                    <div class="space-y-1">
                        <label class="label-premium">Blurb</label>
                        <textarea type="text" name="blurb" class="input-premium" placeholder="Tell us about your student..."></textarea>
                    </div>
                    
                    <button type="button" @click="submitModalForm($el)" class="btn-primary">Create Profile</button>
                </form>
            </template>
            <template x-if="type === 'edit-student'">
                <form :action="'/customer/students/' + studentId" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="label-premium">First Name</label>
                            <input type="text" name="first_name" x-model="firstName" class="input-premium" required>
                        </div>
                        <div class="space-y-1">
                            <label class="label-premium">Last Name</label>
                            <input type="text" name="last_name" x-model="lastName" class="input-premium" required>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="label-premium">Grade</label>
                        <input type="text" name="student_grade" x-model="grade" class="input-premium">
                    </div>
                    <div class="space-y-1">
                        <label class="label-premium">Blurb</label>
                        <textarea type="text" name="blurb" x-model="blurb" class="input-premium" placeholder="Tell us about your student..."></textarea>
                    </div>
                    <button type="button" @click="submitModalForm($el)" class="btn-primary">Update Profile</button>
                </form>
            </template>
            <template x-if="type === 'tutor-schedule-session'">
                <form :action="{{ route('tutor.sessions.store') }}" method="POST" class="space-y-10">
                    @csrf

                    <input type="hidden" name="student_id" :value="studentId">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="label-premium">First Name</label>
                            <input type="text" name="first_name" x-model="firstName" class="input-premium" required>
                        </div>
                        <div class="space-y-1">
                            <label class="label-premium">Last Name</label>
                            <input type="text" name="last_name" x-model="lastName" class="input-premium" required>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 bg-[#212120] text-white rounded-2xl font-black uppercase tracking-[0.3em] text-[11px] shadow-xl hover:bg-black active:scale-[0.98] transition-all">
                        Schedule Session
                    </button>
                </form>
            </template>
        </div>
    </div>

<script>
    window.modalData = function () {
        return {
            open: false,
            isEdit: false,
            studentId: '',
            requestId: '',
            isRecurring: false,
            sessionId: null,
            name: '',
            date: '',
            formId: null,
            tutorId: '',
            subject: '',
            duration: '1:00',
            blurb: '',
            firstName: '',
            lastName: '',
            grade: '',
            time_h: '09',
            time_m: '00',
            time_ampm: 'AM',
            type: 'tutor',
            title: 'New Session',
            errorMessage: '',

            openModal(event) {
                const detail = event?.detail || {};

                this.open = true;
                this.errorMessage = '';
                this.isEdit = detail.isEdit || false;
                this.isRecurring = detail.isRecurring || false;
                this.studentId = detail.studentId || '';
                this.requestId = detail.requestId || '';
                this.sessionId = detail.sessionId ?? null;
                this.firstName = detail.firstName || '';
                this.lastName = detail.lastName || '';
                this.grade = detail.grade || '';
                this.tutorId = detail.tutorId || '';
                this.type = detail.type || 'tutor';
                this.title = detail.title || 'New Session';
                this.blurb = detail.blurb || '';
                this.date = detail.date || '';
                this.subject = detail.subject || '';
                this.duration = detail.duration || '1:00';
                this.time_h = detail.time_h || '09';
                this.time_m = detail.time_m || '00';
                this.time_ampm = detail.time_ampm || 'AM';
            },
        };
    };
</script>

</div>

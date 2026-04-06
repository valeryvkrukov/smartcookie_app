<x-guest-layout>
    <div class="mb-10 text-center">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Join SmartCookie</h2>
        <p class="text-xs text-slate-400 font-medium mt-2 text-balance">Start your journey to excellence. Create your account below.</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-6 text-xs font-bold uppercase">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="register-form" method="POST" action="{{ route('register') }}"
          x-data="{ selfStudent: {{ old('is_self_student') ? 'true' : 'false' }} }"
          class="space-y-10">
        @csrf
        {{-- send flag value to POST --}}
        <input type="hidden" name="is_self_student" :value="selfStudent ? '1' : '0'">

        <!-- TYPE SELECTOR -->
        <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">I am registering as...</p>
            <div class="flex rounded-2xl border border-slate-200 overflow-hidden text-[10px] font-black uppercase tracking-widest">
                <button type="button" @click="selfStudent = false"
                        class="flex-1 py-3 transition-all"
                        :class="!selfStudent ? 'bg-[#212120] text-white' : 'bg-white text-slate-400 hover:text-slate-700'">
                    Parent / Guardian
                </button>
                <button type="button" @click="selfStudent = true"
                        class="flex-1 py-3 transition-all border-l border-slate-200"
                        :class="selfStudent ? 'bg-[#212120] text-white' : 'bg-white text-slate-400 hover:text-slate-700'">
                    Student (Self)
                </button>
            </div>
        </div>

        <!-- SECTION 1: CLIENT INFO -->
        <div class="space-y-6">
            <h3 class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.3em] border-b border-slate-100 pb-2"
                x-text="selfStudent ? '1. Your Info' : '1. Parent / Client Info'"></h3>

            <div class="space-y-1">
                <x-input-label value="Full Name" class="label-premium" />
                <x-text-input name="parent_name" :value="old('parent_name')" class="input-premium" required />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <x-input-label value="Email" class="label-premium" />
                    <x-text-input name="parent_email" type="email" :value="old('parent_email')" class="input-premium" required />
                </div>
                <div class="space-y-1">
                    <x-input-label value="Phone" class="label-premium" />
                    <x-text-input name="phone" :value="old('phone')" class="input-premium" placeholder="555-0123" required />
                </div>
            </div>

            <div class="space-y-1">
                <x-input-label value="Home Address" class="label-premium" />
                <x-text-input name="address" :value="old('address')" class="input-premium" placeholder="123 Street, City, State" required />
            </div>
        </div>

        <!-- SECTION 2: STUDENT DETAILS — hidden when selfStudent = true -->
        <div x-show="!selfStudent" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="space-y-6">
            <h3 class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.3em] border-b border-slate-100 pb-2">2. Student Details</h3>

            <div class="space-y-1">
                <x-input-label value="Student Full Name" class="label-premium" />
                <x-text-input name="student_name" :value="old('student_name')" class="input-premium" />
            </div>

            <div class="space-y-1">
                <x-input-label value="Grade" class="label-premium" />
                <x-text-input name="student_grade" :value="old('student_grade')" class="input-premium" placeholder="e.g. 10th" />
            </div>

            <div class="space-y-1">
                <x-input-label value="School / College" class="label-premium" />
                <x-text-input name="student_school" :value="old('student_school')" class="input-premium" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <x-input-label value="Student Phone" class="label-premium" />
                    <span class="text-slate-300 text-[8px] font-black uppercase tracking-widest ml-1">optional</span>
                    <x-text-input name="student_phone" :value="old('student_phone')" class="input-premium" placeholder="555-0123" />
                </div>
                <div class="space-y-1">
                    <x-input-label value="Student Email" class="label-premium" />
                    <span class="text-slate-300 text-[8px] font-black uppercase tracking-widest ml-1">optional</span>
                    <x-text-input name="student_email" type="email" :value="old('student_email')" class="input-premium" placeholder="student@email.com" />
                </div>
            </div>

            <div class="space-y-1">
                <x-input-label value="Student Home Address" class="label-premium" />
                <span class="text-slate-300 text-[8px] font-black uppercase tracking-widest ml-1">optional</span>
                <x-text-input name="student_address" :value="old('student_address')" class="input-premium" placeholder="123 Street, City, State" />
            </div>
        </div>

        <!-- SECTION 3: SECURITY -->
        <div class="space-y-6">
            <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] border-b border-slate-100 pb-2">
                <span x-text="selfStudent ? '2.' : '3.'"></span> Security
            </h3>
            <div class="space-y-1">
                <x-input-label value="Password" class="label-premium" />
                <x-text-input type="password" name="password" class="input-premium" required />
            </div>
            <div class="space-y-1">
                <x-input-label value="Confirm" class="label-premium" />
                <x-text-input type="password" name="password_confirmation" class="input-premium" required />
            </div>
        </div>

        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

        @if(config('services.recaptcha.key'))
            <button type="submit"
                    class="g-recaptcha btn-primary w-full"
                    data-sitekey="{{ config('services.recaptcha.key') }}"
                    data-callback='onSubmit'
                    data-action='submit'>
                Complete Registration
            </button>
        @else
            <button type="submit" class="btn-primary w-full">
                Complete Registration
            </button>
        @endif

        @if (Route::has('login'))
            <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest pt-4">
                Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Login</a>
            </p>
        @endif
    </form>

    @if(config('services.recaptcha.key'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script>
            function onSubmit(token) {
                document.getElementById("g-recaptcha-response").value = token;
                document.getElementById("register-form").submit();
            }
        </script>
    @endif
</x-guest-layout>

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

    <form id="register-form" method="POST" action="{{ route('register') }}" class="space-y-10">
        @csrf

        <!-- PARENT / BILLING INFO -->
        <div class="space-y-6">
            <h3 class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.3em] border-b border-slate-100 pb-2">1. Parent / Client Info</h3>
            
            <div class="space-y-4">
                <div class="space-y-1">
                    <x-input-label value="Parent Full Name" class="label-premium" />
                    <x-text-input name="parent_name" :value="old('parent_name')" class="input-premium" required />
                </div>
                
                <div class="grid grid-cols-2 gap-4">
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
        </div>

        <!-- STUDENT INFO -->
        <div class="space-y-6">
            <h3 class="text-[11px] font-black text-indigo-600 uppercase tracking-[0.3em] border-b border-slate-100 pb-2">2. Student Details</h3>
            
            <div class="space-y-4">
                <div class="space-y-1">
                    <x-input-label value="Student Full Name" class="label-premium" />
                    <x-text-input name="student_name" :value="old('student_name')" class="input-premium" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <x-input-label value="Grade" class="label-premium" />
                        <x-text-input name="student_grade" :value="old('student_grade')" class="input-premium" placeholder="e.g. 10th" required />
                    </div>
                    <div class="space-y-1">
                        <x-input-label value="College" class="label-premium" />
                        <x-text-input name="student_school" :value="old('student_school')" class="input-premium" required />
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCOUNT -->
        <div class="space-y-6">
            <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] border-b border-slate-100 pb-2">3. Security</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <x-input-label value="Password" class="label-premium" />
                    <x-text-input type="password" name="password" class="input-premium" required />
                </div>
                <div class="space-y-1">
                    <x-input-label value="Confirm" class="label-premium" />
                    <x-text-input type="password" name="password_confirmation" class="input-premium" required />
                </div>
            </div>
        </div>

        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

        <button type="submit" 
                class="g-recaptcha btn-primary w-full" 
                data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}" 
                data-callback='onSubmit' 
                data-action='submit'>
            Complete Registration
        </button>

        @if (Route::has('login'))
            <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest pt-4">
                Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Login</a>
            </p>
        @endif
    </form>


    <!-- Script from Google and reCAPTCHA integration -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        function onSubmit(token) {
            console.log('reCAPTCHA token received:', token);
            document.getElementById("g-recaptcha-response").value = token;
            document.getElementById("register-form").submit();
        }
    </script>
</x-guest-layout>

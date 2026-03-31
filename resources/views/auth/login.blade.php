<x-guest-layout>
    <div class="mb-10 text-center">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Welcome Back</h2>
        <p class="text-xs text-slate-400 font-medium mt-2 text-balance">Please enter your credentials to access the portal.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-8">
        @csrf

        <!-- Email Address -->
        <div class="space-y-2">
            <x-input-label for="email" value="Email Address" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1" />
            <x-text-input id="email" class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <x-input-label for="password" value="Password" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1" />
                @if (Route::has('password.request'))
                    <a class="text-[9px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800" href="{{ route('password.request') }}">Forgot?</a>
                @endif
            </div>
            <x-text-input id="password" class="w-full border-0 border-b-2 border-slate-100 focus:border-[#212120] focus:ring-0 bg-transparent py-3 font-bold text-slate-800" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember_me" class="inline-flex items-center group cursor-pointer">
                <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-2 border-slate-200 text-[#212120] focus:ring-0" name="remember">
                <span class="ms-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest group-hover:text-slate-700 transition-colors">Keep me signed in</span>
            </label>
        </div>

        <div class="pt-4">
            <button class="w-full py-5 bg-[#212120] text-white rounded-2xl font-black uppercase tracking-[0.3em] text-[11px] shadow-xl hover:bg-black active:scale-95 transition-all">
                Sign In to Portal
            </button>
        </div>

        @if (Route::has('register'))
            <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest pt-4">
                Don't have an account? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Register</a>
            </p>
        @endif
    </form>
</x-guest-layout>

<x-app-layout>
    <x-slot name="header_title">Dashboard</x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    
        <!-- 1. Credits (Amber) -->
        <a href="{{ route('customer.credits.index') }}" 
        class="relative group overflow-hidden p-10 bg-amber-500 rounded-[3rem] shadow-2xl shadow-amber-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-money"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Credits: {{ $balance }}</h3>
                <p class="text-amber-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Billing & Top-up</p>
            </div>
        </a>

        <!-- 2. Students (Sky) -->
        <a href="{{ route('customer.students.index') }}" 
        class="relative group overflow-hidden p-10 bg-sky-500 rounded-[3rem] shadow-2xl shadow-sky-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-id-badge"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">My Students</h3>
                <p class="text-sky-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Manage Profiles</p>
            </div>
        </a>

        <!-- 3. Weekly Schedule (Indigo/Deep Sky) -->
        <a href="{{ route('customer.calendar.index') }}" 
        class="relative group overflow-hidden p-10 bg-indigo-900 rounded-[3rem] shadow-2xl shadow-indigo-900/30 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-calendar"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Schedule</h3>
                <p class="text-indigo-200 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Weekly Sessions</p>
            </div>
        </a>

        <!-- 4. Agreements (Rose) -->
        <a href="{{ route('customer.agreements.index') }}" 
        class="relative group overflow-hidden p-10 bg-rose-600 rounded-[3rem] shadow-2xl shadow-rose-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-write"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Agreements</h3>
                <p class="text-rose-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Legal & Policies</p>
            </div>
        </a>

        <!-- 5. Profile (Slate) -->
        <a href="{{ route('profile.edit') }}" 
        class="relative group overflow-hidden p-10 bg-slate-600 rounded-[3rem] shadow-2xl shadow-slate-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-user"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">My Settings</h3>
                <p class="text-slate-200 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Account Security</p>
            </div>
        </a>

    </div>

</x-app-layout>

<x-app-layout>
    <x-slot name="header_title">
        Admin Control Center
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    
        <!-- 1. Main Calendar (Indigo) -->
        <a href="{{ route('admin.calendar.index') }}" 
        class="relative group overflow-hidden p-10 bg-indigo-600 rounded-[3rem] shadow-2xl shadow-indigo-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-calendar"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Global Calendar</h3>
                <p class="text-indigo-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">View All Tutor Sessions</p>
            </div>
        </a>

        <!-- 2. User Directory (Slate/Black) -->
        <a href="{{ route('admin.users.index') }}" 
        class="relative group overflow-hidden p-10 bg-slate-900 rounded-[3rem] shadow-2xl shadow-slate-900/20 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-layout-grid3"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">User Directory</h3>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Account Management</p>
            </div>
        </a>
        

        <!-- 3. Agreements (Rose/Pink) -->
        <a href="{{ route('admin.agreements.index') }}" 
        class="relative group overflow-hidden p-10 bg-rose-600 rounded-[3rem] shadow-2xl shadow-rose-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-write"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Agreements</h3>
                <p class="text-rose-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Compliance & Legal</p>
            </div>
        </a>

        <!-- 4. Global Financials (Violet) -->
        <a href="{{ route('admin.financials.index') }}" 
        class="relative group overflow-hidden p-10 bg-violet-600 rounded-[3rem] shadow-2xl shadow-violet-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-stats-up"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Financials</h3>
                <p class="text-violet-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Rates & Payouts</p>
            </div>
        </a>

        <!-- 5. System Logs (Cyan) -->
        <a href="{{ route('admin.logs.index') }}" 
        class="relative group overflow-hidden p-10 bg-cyan-600 rounded-[3rem] shadow-2xl shadow-cyan-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-settings"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">System Logs</h3>
                <p class="text-cyan-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Audit Trail</p>
            </div>
        </a>

        <!-- 6. Profile (Slate/Settings) -->
        <a href="{{ route('profile.edit') }}" 
        class="relative group overflow-hidden p-10 bg-slate-600 rounded-[3rem] shadow-2xl shadow-slate-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-user"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">My Settings</h3>
                <p class="text-slate-200 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Profile & Security</p>
            </div>
        </a>

    </div>
</x-app-layout>

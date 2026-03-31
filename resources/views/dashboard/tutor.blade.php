<x-app-layout>
    <x-slot name="header_title">Dashboard</x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    
        <!-- 1. My Students (Sky) -->
        <a href="{{ route('tutor.students.index') }}" 
        class="relative group overflow-hidden p-10 bg-sky-600 rounded-[3rem] shadow-2xl shadow-sky-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-id-badge"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">My Students</h3>
                <p class="text-sky-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Roster & Contact Info</p>
            </div>
        </a>

        <!-- 2. Weekly Calendar (Indigo) -->
        <a href="{{ route('tutor.calendar.index') }}" 
        class="relative group overflow-hidden p-10 bg-indigo-600 rounded-[3rem] shadow-2xl shadow-indigo-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-calendar"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Weekly Calendar</h3>
                <p class="text-indigo-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Schedule & Manage</p>
            </div>
        </a>

        <!-- 3. Timesheets (Emerald) -->
        <a href="{{ route('tutor.timesheets.index') }}" 
        class="relative group overflow-hidden p-10 bg-emerald-600 rounded-[3rem] shadow-2xl shadow-emerald-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-timer"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">Timesheets</h3>
                <p class="text-emerald-100 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Log Hours & Billing</p>
            </div>
        </a>

        <!-- 4. My Profile (Slate) -->
        <a href="{{ route('profile.edit') }}" 
        class="relative group overflow-hidden p-10 bg-slate-700 rounded-[3rem] shadow-2xl shadow-slate-200/50 hover:-translate-y-2 transition-all duration-500">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl text-white mb-8 shadow-inner">
                    <i class="ti-user"></i>
                </div>
                <h3 class="text-2xl font-black text-white tracking-tight">My Profile</h3>
                <p class="text-slate-200 text-[10px] font-black uppercase tracking-[0.2em] mt-3 opacity-70">Bio & Account Settings</p>
            </div>
        </a>

    </div>

</x-app-layout>

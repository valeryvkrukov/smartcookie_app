<x-app-layout>
    <x-slot name="header_title text-slate-900">Welcome, {{ auth()->user()->first_name }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        
        {{-- ── Profile card: link to account settings --}}
        <a href="{{ route('profile.edit') }}" class="group bg-white p-8 rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-500 transition-all duration-200 hover:shadow-md">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                👤
            </div>
            <h3 class="mt-4 font-bold text-slate-800 text-lg">My Profile</h3>
            <p class="text-sm text-slate-500 mt-1">Manage your personal information and settings.</p>
        </a>

        {{-- ── Students card: link to student list --}}
        <a href="{{ route('students.index') }}" class="group bg-white p-8 rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-500 transition-all">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-2xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                🎓
            </div>
            <h3 class="mt-4 font-bold text-slate-800 text-lg">Students</h3>
            <p class="text-sm text-slate-500 mt-1">View and manage student profiles and details.</p>
        </a>

        {{-- ── Sessions card: link to tutoring calendar --}}
        <a href="{{ route('tutor.sessions.index') }}" class="group bg-white p-8 rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-500 transition-all">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl group-hover:bg-amber-600 group-hover:text-white transition-colors">
                📅
            </div>
            <h3 class="mt-4 font-bold text-slate-800 text-lg">Sessions</h3>
            <p class="text-sm text-slate-500 mt-1">Schedule and track your weekly tutoring sessions.</p>
        </a>

        {{-- ── Timesheets card: link to billing log --}}
        <a href="{{ route('tutor.timesheets.index') }}" class="group bg-white p-8 rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-500 transition-all">
            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center text-2xl group-hover:bg-rose-600 group-hover:text-white transition-colors">
                📝
            </div>
            <h3 class="mt-4 font-bold text-slate-800 text-lg">Timesheets</h3>
            <p class="text-sm text-slate-500 mt-1">Log completed sessions and manage billing.</p>
        </a>

        {{-- ── Agreements card: link to legal documents --}}
        <a href="{{ route('agreements.index') }}" class="group bg-white p-8 rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-500 transition-all">
            <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center text-2xl group-hover:bg-sky-600 group-hover:text-white transition-colors">
                ✍️
            </div>
            <h3 class="mt-4 font-bold text-slate-800 text-lg">Agreements</h3>
            <p class="text-sm text-slate-500 mt-1">Review and sign legal documents and policies.</p>
        </a>

    </div>
</x-app-layout>

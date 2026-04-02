<aside class="fixed inset-y-0 left-0 z-40 w-20 hover:w-64 bg-[#212120] transition-all duration-500 ease-in-out overflow-hidden group border-r border-white/5 shadow-2xl">
    <div class="flex flex-col h-full py-8 px-4 justify-between">
        
        <!-- Logo -->
        <div class="flex items-center mb-12 px-2">
            <div class="w-10 h-10 bg-indigo-600 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-indigo-500/20">
                <span class="text-white font-black text-xl">S</span>
            </div>
            <span class="ml-4 text-white font-black text-lg tracking-tight opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">SmartCookie</span>
        </div>

        <!-- Menu items -->
        <nav class="flex-1 space-y-3">
            @php
                $links = [
                    ['route' => 'dashboard', 'icon' => 'ti-layout-grid2', 'label' => 'Dashboard'],
                    ['route' => auth()->user()->role === 'admin' ? 'admin.calendar.index' : 'tutor.calendar.index', 'icon' => 'ti-calendar', 'label' => 'Schedule', 'role' => ['admin', 'tutor']],
                    ['route' => 'tutor.students.index', 'icon' => 'ti-id-badge', 'label' => 'My Students', 'role' => 'tutor'],
                    ['route' => 'customer.calendar.index', 'icon' => 'ti-calendar', 'label' => 'Family Schedule', 'role' => 'customer'],
                    ['route' => 'customer.students.index', 'icon' => 'ti-id-badge', 'label' => 'My Students', 'role' => 'customer'],
                    ['route' => 'admin.users.index', 'icon' => 'ti-user', 'label' => 'Directory', 'role' => 'admin'],
                    ['route' => 'admin.financials.index', 'icon' => 'ti-wallet', 'label' => 'Financials', 'role' => 'admin'],
                    ['route' => auth()->user()->role === 'admin' ? 'admin.agreements.index' : 'customer.agreements.index', 'icon' => 'ti-write', 'label' => 'Agreements', 'role' => ['admin', 'customer']],
                    ['route' => 'tutor.timesheets.index', 'icon' => 'ti-timer', 'label' => 'Timesheets', 'role' => 'tutor'],
                    ['route' => 'customer.credits.index', 'icon' => 'ti-money', 'label' => 'Billing & Credits', 'role' => 'customer'],
                    ['route' => 'admin.logs.index', 'icon' => 'ti-settings', 'label' => 'System Logs', 'role' => 'admin'],
                ];
            @endphp

            @foreach($links as $link)
                @if(!isset($link['role']) || in_array(auth()->user()->role, (array)$link['role']))
                <a href="{{ route($link['route']) }}"
                    class="flex items-center p-3 rounded-2xl transition-all duration-300 group/item {{ request()->routeIs(str_replace('.index', '.*', $link['route'])) ? 'bg-white/10 text-white shadow-inner' : 'text-slate-500 hover:bg-white/5 hover:text-slate-200' }}">
                    <i class="{{ $link['icon'] }} text-xl shrink-0"></i>
                    <span class="ml-4 font-bold text-xs uppercase tracking-[0.2em] opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">{{ $link['label'] }}</span>
                </a>
                @endif
            @endforeach
        </nav>

        <!-- Profile Link -->
        <a href="{{ route('profile.edit') }}" class="flex items-center p-2 rounded-2xl bg-white/5 hover:bg-white/10 transition-colors">
            <div class="w-8 h-8 rounded-xl overflow-hidden bg-slate-700 shrink-0 border border-white/10">
                <img src="{{ auth()->user()->photo_url ?? asset('images/generic-avatar.png') }}" class="w-full h-full object-cover">
            </div>
            <div class="ml-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <p class="text-[10px] font-black text-white uppercase tracking-widest leading-none">{{ auth()->user()->first_name }}</p>
                <p class="text-[8px] text-slate-500 font-bold uppercase mt-1">{{ auth()->user()->role }}</p>
            </div>
        </a>

        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" 
                    class="w-full flex items-center p-3 rounded-2xl text-rose-400 hover:bg-rose-500/10 hover:text-rose-500 transition-all duration-300 group/logout">
                <i class="ti-power-off text-xl shrink-0"></i>
                <span class="ml-4 font-bold text-[10px] uppercase tracking-[0.2em] opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">Sign Out</span>
            </button>
        </form>
    </div>
</aside>

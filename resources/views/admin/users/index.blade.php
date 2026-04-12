<x-app-layout>
    <x-slot name="header_title">User Directory</x-slot>

    @if($pendingCount > 0)
    <div class="mb-6 flex items-center gap-4 p-5 bg-amber-50 border border-amber-200 rounded-[2rem] shadow-sm">
        <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-lg flex-shrink-0">
            <i class="ti-time"></i>
        </div>
        <div class="flex-1">
            <p class="text-xs font-black text-amber-800">{{ $pendingCount }} {{ Str::plural('client', $pendingCount) }} awaiting payment confirmation</p>
            <p class="text-[9px] text-amber-600 mt-0.5">Look for the amber badge on client cards below.</p>
        </div>
        <a href="{{ route('admin.users.index', ['pending' => 1]) }}"
           class="px-4 py-2 bg-amber-500 text-white text-[9px] font-black uppercase tracking-widest rounded-xl hover:bg-amber-600 transition-colors whitespace-nowrap">
            View Pending
        </a>
    </div>
    @endif

    <!-- Header: Search & Tabs -->
    <div class="mb-10 space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Tabs (Modern Segmented Control) -->
            <div class="flex bg-white p-1.5 rounded-2xl border border-slate-100 shadow-sm overflow-x-auto">
                @foreach(['all' => 'All', 'admin' => 'Admins', 'tutor' => 'Tutors', 'customer' => 'Clients', 'student' => 'Students'] as $val => $label)
                    <a href="{{ route('admin.users.index', ['role' => $val == 'all' ? '' : $val]) }}" 
                       class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ (request('role') == $val || (request('role') == '' && $val == 'all')) ? 'bg-[#212120] text-white shadow-lg' : 'text-slate-400 hover:text-slate-600' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <!-- Search -->
            <form action="{{ route('admin.users.index') }}" method="GET" class="relative group w-full md:w-72">
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="w-full pl-5 pr-12 py-3 bg-white border-none rounded-2xl text-xs font-bold shadow-sm focus:ring-2 focus:ring-indigo-500 transition-all" 
                       placeholder="Search directory...">
                <button class="absolute right-4 top-3 text-slate-400 group-hover:text-indigo-600 transition-colors">
                    <i class="ti-search"></i>
                </button>
            </form>
        </div>
    </div>

    <ul class="divide-y divide-slate-100 bg-white rounded-2xl border border-slate-100 shadow-xl shadow-slate-200/40">
        @foreach($users as $user)
            <li class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center text-lg font-bold">
                        {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-black text-slate-900">{{ $user->full_name }}</div>
                        <div class="text-xs text-slate-400">{{ $user->email }}</div>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest {{ $user->role === 'admin' ? 'bg-indigo-50 text-indigo-600' : ($user->role === 'tutor' ? 'bg-emerald-50 text-emerald-600' : ($user->role === 'customer' ? 'bg-amber-50 text-amber-600' : 'bg-rose-50 text-rose-600')) }}">
                        {{ $user->role }}
                    </span>
                    @if($user->role === 'customer')
                    <button type="button" 
                        onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: { type: 'send-agreement', studentId: '{{ $user->id }}', title: {{ json_encode('Send Document to '.$user->full_name) }} } }))"
                        class="text-[9px] font-black uppercase tracking-widest text-indigo-600 hover:text-black transition-colors">
                        Send Policy
                    </button>
                    @endif
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="text-slate-300 hover:text-indigo-600 transition-colors"><i class="ti-pencil"></i></a>
                    <button type="button"
                        onclick="
                            event.preventDefault();
                            event.stopPropagation();
                            window.dispatchEvent(new CustomEvent('confirm-user-delete', {
                                detail: {
                                    name: '{{ $user->full_name }}',
                                    formId: 'delete-user-{{ $user->id }}'
                                }
                            }));
                        "
                        class="text-slate-300 hover:text-rose-500 transition-colors ml-2"
                        title="Delete"
                    >
                        <i class="ti-trash"></i>
                    </button>
                    <form id="delete-user-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </li>
        @endforeach
    </ul>

    <!-- User Grid --
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
        @foreach($users as $user)
        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-8 flex flex-col group hover:-translate-y-1 transition-all duration-500">
            <!- Role Badge & Actions --
            <div class="flex justify-between items-start mb-6">
                <span class="px-3 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest 
                    {{ $user->role === 'admin' ? 'bg-indigo-50 text-indigo-600' : '' }}
                    {{ $user->role === 'tutor' ? 'bg-emerald-50 text-emerald-600' : '' }}
                    {{ $user->role === 'customer' ? 'bg-amber-50 text-amber-600' : '' }}
                    {{ $user->role === 'student' ? 'bg-rose-50 text-rose-600' : '' }}">
                    {{ $user->role }}
                </span>
                @if($user->is_inactive)
                <span class="px-2.5 py-1 bg-slate-100 text-slate-400 rounded-lg text-[7px] font-black uppercase tracking-widest">
                    Inactive
                </span>
                @endif
                <div class="flex items-center space-x-2">
                    @if($user->role === 'customer' && $user->credit?->pending_payment_amount)
                    <span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-[7px] font-black uppercase tracking-widest flex items-center gap-1">
                        <i class="ti-time"></i> ${{ number_format($user->credit->pending_payment_amount, 2) }}
                    </span>
                    @endif
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="text-slate-300 hover:text-indigo-600 transition-colors"><i class="ti-pencil"></i></a>
                </div>
            </div>

            <!- User Info --
            <div class="flex items-center space-x-4 mb-6">
                <div class="w-14 h-14 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center text-xl font-bold group-hover:bg-[#212120] group-hover:text-white transition-colors duration-500">
                    {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                </div>
                <div>
                    <h3 class="font-black text-slate-900 tracking-tight">{{ $user->full_name }}</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter truncate w-32">{{ $user->email }}</p>
                </div>
                {{-- Send Policy Button (only for customers) --}}
                @if($user->role === 'customer')
                <button type="button" 
                    onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: { type: 'send-agreement', studentId: '{{ $user->id }}', title: {{ json_encode('Send Document to '.$user->full_name) }} } }))"
                    class="text-[9px] font-black uppercase tracking-widest text-indigo-600 hover:text-black transition-colors">
                    Send Policy
                </button>
                @endif
            </div>

            <!- Stats/Meta --
            <div class="mt-auto pt-6 border-t border-slate-50 flex justify-between items-center">
                <a href="mailto:{{ $user->email }}" class="text-[9px] font-black uppercase tracking-widest text-indigo-600 hover:underline">Message</a>
                
                <button type="button" 
                    onclick="
                        event.preventDefault(); 
                        event.stopPropagation(); 
                        window.dispatchEvent(new CustomEvent('confirm-user-delete', { 
                            detail: { 
                                name: '{{ $user->full_name }}', 
                                formId: 'delete-user-{{ $user->id }}' 
                            } 
                        }));
                    "
                    class="text-[9px] font-black uppercase tracking-widest text-rose-500 hover:text-rose-700 transition-colors">
                    Delete
                </button>
            </div>

            <form id="delete-user-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
        @endforeach
    </div>-->

    <!-- Pagination -->
    <div class="mt-12">
        {{ $users->links() }}
    </div>
</x-app-layout>

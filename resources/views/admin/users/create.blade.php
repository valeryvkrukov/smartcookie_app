<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 transition">
            User Directory >
        </a>
        New Profile
    </x-slot>

    <form 
        x-data="{ 
            fname: '{{ $user->first_name ?? '' }}', 
            lname: '{{ $user->last_name ?? '' }}' 
        }"
        action="{{ route('admin.users.store') }}" 
        method="POST" 
        class="max-w-5xl mx-auto pb-20">
        @csrf
        @method('POST')

        @if ($errors->any())
            <div class="mb-6 p-4 bg-rose-50 text-rose-600 rounded-2xl text-xs font-bold uppercase border border-rose-100">
                @foreach ($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
            
            <!-- LEFT COLUMN: Main Information -->
            <div class="lg:col-span-2 space-y-8 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/40">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="label-premium">First Name</label>
                        <input x-model="fname" type="text" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" class="input-premium" required>
                    </div>
                    <div class="space-y-2">
                        <label class="label-premium">Last Name</label>
                        <input x-model="lname" type="text" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" class="input-premium" required>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="label-premium">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="input-premium" required>
                </div>

                <div class="space-y-2">
                    <label class="label-premium">System Role</label>
                    <select name="role" class="input-premium">
                        <option value="admin">Admin</option>
                        <option value="tutor">Tutor</option>
                        <option value="customer">Customer (Parent)</option>
                        <option value="student">Student</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="label-premium">Password</label>
                    <input type="password" name="password" class="input-premium" value="{{ $temporaryPassword }}" {{ isset($user) ? '' : 'required' }}>
                </div>
                <div class="space-y-2">
                    <label class="label-premium">Confirm Password</label>
                    <input type="password" name="password_confirmation" value="{{ $temporaryPassword }}" class="input-premium" {{ isset($user) ? '' : 'required' }}>
                </div>
            </div>

            <!-- RIGHT COLUMN: Role settings (Contextual Settings) -->
            <div class="space-y-8">

                <!-- Actions -->
                <div class="bg-[#212120] p-8 rounded-[3rem] shadow-2xl shadow-slate-900/20 text-white">
                    <h3 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-6">Actions</h3>
                    <div class="space-y-4">
                        <button type="submit" class="w-full py-4 bg-white text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-50 transition-all">
                            Save Changes
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="block w-full py-4 bg-transparent border border-white/20 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-white/5 transition-all">
                            Discard
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
</x-app-layout>
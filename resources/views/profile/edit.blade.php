<x-app-layout>
    <x-slot name="header_title">Account Settings</x-slot>

    <div class="max-w-5xl mx-auto pb-20 space-y-10">
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" 
              x-data="{ photoPreview: null }" class="space-y-10">
            @csrf
            @method('patch')

            
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/40">
                <div class="flex flex-col md:flex-row gap-12">
                    {{-- ── Photo column: avatar with live file-reader preview --}}
                    <div class="flex flex-col items-center space-y-4">
                        <div class="relative group">
                            <div class="w-32 h-32 rounded-[2.5rem] overflow-hidden bg-slate-100 border-4 border-white shadow-xl">
                                <template x-if="!photoPreview">
                                    <img src="{{ $user->photo_url }}" 
                                         class="w-full h-full object-cover">
                                </template>
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" class="w-full h-full object-cover">
                                </template>
                            </div>
                            <label class="absolute -bottom-2 -right-2 w-10 h-10 bg-indigo-600 text-white rounded-2xl flex items-center justify-center cursor-pointer shadow-lg hover:bg-black transition-colors">
                                <i class="ti-camera text-sm"></i>
                                <input type="file" name="photo" class="hidden" @change="
                                    const file = $event.target.files[0];
                                    const reader = new FileReader();
                                    reader.onload = (e) => { photoPreview = e.target.result; };
                                    reader.readAsDataURL(file);
                                ">
                            </label>
                        </div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Profile Photo</p>
                    </div>

                    {{-- ── Name & bio column: first name, last name, tagline --}}
                    <div class="flex-1 space-y-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <x-input-label value="First Name" class="label-premium" />
                                <input name="first_name" type="text" class="input-premium" value="{{ old('first_name', $user->first_name) }}" required>
                            </div>
                            <div class="space-y-2">
                                <x-input-label value="Last Name" class="label-premium" />
                                <input name="last_name" type="text" class="input-premium" value="{{ old('last_name', $user->last_name) }}" required>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <x-input-label value="Short Blurb (Tagline)" class="label-premium" />
                            <input name="blurb" type="text" class="input-premium" value="{{ old('blurb', $user->blurb) }}" placeholder="e.g. Expert SAT Math Tutor with 5+ years experience">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Section 2: Contact & location details --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl">
                    <h3 class="label-premium mb-8 text-indigo-600">Contact Details</h3>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <x-input-label value="Email Address" class="label-premium" />
                            <input name="email" type="email" class="input-premium" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="space-y-2">
                            <x-input-label value="Phone Number" class="label-premium" />
                            <input name="phone" type="text" class="input-premium" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 000-0000">
                        </div>
                        <div class="space-y-2">
                            <x-input-label value="Home Address" class="label-premium" />
                            <input name="address" type="text" class="input-premium" value="{{ old('address', $user->address) }}" placeholder="123 Luxury St, New York, NY">
                        </div>
                    </div>
                </div>

                {{-- ── Section 3: System preferences and timezone --}}
                <div class="bg-[#212120] p-10 rounded-[3rem] shadow-2xl text-white">
                    <h3 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-8">System Preferences</h3>
                    <div class="space-y-6 pt-4 border-t border-white/10">
                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Email Notifications</span>
                            <div class="relative">
                                <input type="checkbox" name="is_subscribed" value="1" {{ $user->is_subscribed ? 'checked' : '' }} class="peer hidden">
                                <div class="w-10 h-5 bg-white/10 rounded-full peer-checked:bg-indigo-500 transition-all"></div>
                                <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>
                        <p class="text-[8px] text-white/20 uppercase tracking-widest italic">Receive session reminders & updates</p>
                    </div>
                    
                    <div class="space-y-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60 ml-1">Time Zone (Crucial for Calendar)</label>
                            <select name="time_zone" class="w-full bg-white/5 border-0 border-b-2 border-white/10 focus:border-white focus:ring-0 py-3 font-bold text-white transition-colors">
                                @foreach(timezone_identifiers_list() as $tz)
                                    <option value="{{ $tz }}" {{ old('time_zone', $user->time_zone) == $tz ? 'selected' : '' }} class="text-slate-900">{{ $tz }}</option>
                                @endforeach
                            </select>
                            <p class="text-[9px] text-white/30 mt-2 italic">* This ensures your 1:45 PM is correctly synced across all devices.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Save: full-width submit button spanning both columns --}}
            <div>
                @if (session('status') === 'profile-updated')
                    <p class="text-center text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-4">Saved successfully.</p>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-rose-50 rounded-2xl text-rose-600 text-[11px] font-bold">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <button type="submit" class="w-full py-5 bg-[#212120] text-white rounded-2xl font-black uppercase tracking-[0.3em] text-[11px] shadow-xl hover:bg-indigo-600 transition-all active:scale-95">
                    Save All Changes
                </button>
            </div>
        </form>

        {{-- ── Security & password: rendered in a separate card --}}
        <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl">
             @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>

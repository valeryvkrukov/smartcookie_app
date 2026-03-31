<section>
    <form method="post" action="{{ route('profile.update') }}" class="space-y-8">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-2">
                <x-input-label for="first_name" value="First Name" class="label-premium" />
                <input id="first_name" name="first_name" type="text" class="input-premium" value="{{ old('first_name', $user->first_name) }}" required autofocus />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>

            <div class="space-y-2">
                <x-input-label for="last_name" value="Last Name" class="label-premium" />
                <input id="last_name" name="last_name" type="text" class="input-premium" value="{{ old('last_name', $user->last_name) }}" required />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>
        </div>

        <div class="space-y-2">
            <x-input-label for="email" value="Email Address" class="label-premium" />
            <input id="email" name="email" type="email" class="input-premium" value="{{ old('email', $user->email) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button class="btn-primary !w-auto px-12">Save Changes</button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-[10px] font-black uppercase tracking-widest text-emerald-500">Saved Successfully</p>
            @endif
        </div>
    </form>
</section>

<x-app-layout>
    <x-slot name="header_title">Financial Hub</x-slot>

    <div class="max-w-5xl mx-auto space-y-12">

        @if(session('payment_instructions'))
            <div class="p-8 rounded-[2.5rem] bg-emerald-50 border border-emerald-100 shadow-sm">
                <p class="text-sm font-black text-emerald-900">Payment instructions for {{ session('payment_instructions.method') }}:</p>
                <p class="mt-4 text-sm text-slate-600">Send <span class="font-black text-slate-900">${{ session('payment_instructions.amount') }}</span> to <span class="font-black text-slate-900">{{ session('payment_instructions.email') }}</span>.</p>
                <p class="mt-2 text-sm text-slate-600">Use note: <span class="font-black text-slate-900">{{ session('payment_instructions.note') }}</span></p>
                <p class="mt-2 text-sm text-slate-600">This covers <span class="font-black text-slate-900">{{ session('payment_instructions.credits') }}</span> credit(s).</p>
                <p class="mt-3 text-[10px] uppercase tracking-[0.3em] text-slate-400">Credits will be added after payment confirmation.</p>
            </div>
        @endif

        @if(session('success'))
            <div class="p-6 rounded-[2rem] bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm font-bold">
                <i class="ti-check mr-2"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-6 rounded-[2rem] bg-rose-50 border border-rose-100 text-rose-700 text-sm font-bold">
                <i class="ti-alert mr-2"></i> {{ session('error') }}
            </div>
        @endif

        <!-- MAIN CREDIT CARD -->
        <div class="relative overflow-hidden bg-[#1A1A19] rounded-[3.5rem] p-12 shadow-2xl shadow-indigo-500/20 group">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-600/20 blur-[100px] rounded-full group-hover:bg-indigo-600/30 transition-all duration-700"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-emerald-600/10 blur-[100px] rounded-full group-hover:bg-emerald-600/20 transition-all duration-700"></div>

            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="text-center md:text-left">
                    <p class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-500 mb-2">Available Credits</p>
                    <h2 class="text-7xl font-black text-white tracking-tighter drop-shadow-2xl">
                        <span class="text-indigo-400">✦</span> {{ number_format($balance, 2) }}
                    </h2>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-3">
                        1 credit = 60-min session &nbsp;·&nbsp; 0.5 = 30-min &nbsp;·&nbsp; 1.5 = 90-min
                    </p>
                </div>
                <div class="flex items-center space-x-3 bg-white/5 backdrop-blur-xl p-4 rounded-3xl border border-white/10">
                    <div class="w-10 h-10 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center">
                        <i class="ti-shield text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-white uppercase tracking-widest leading-none">Secured</p>
                        <p class="text-[8px] font-bold text-slate-500 uppercase mt-1">PCI Compliant</p>
                    </div>
                </div>
            </div>
        </div>

        @if(!$ratePerCredit)
            {{-- LOCKED STATE: admin has not yet set pricing --}}
            <div class="bg-slate-50 border border-slate-200 rounded-[3rem] p-12 text-center space-y-4">
                <div class="w-16 h-16 bg-slate-200 text-slate-400 rounded-3xl flex items-center justify-center text-3xl mx-auto">
                    <i class="ti-lock"></i>
                </div>
                <h3 class="text-xl font-black text-slate-700 tracking-tight">Purchasing Not Yet Available</h3>
                <p class="text-sm text-slate-400 max-w-sm mx-auto leading-relaxed">
                    Credit pricing for your account hasn't been set up yet.
                    Please contact your administrator to enable purchasing.
                </p>
            </div>
        @else
            {{-- PURCHASE SECTION --}}
            <div x-data="{ selectedPack: {{ $isFirstPurchase ? 1 : 4 }}, paymentMethod: '' }" class="space-y-8">

                @if($isFirstPurchase)
                    {{-- FIRST-TIME BUYER: only 1 credit --}}
                    <div class="bg-indigo-50 border border-indigo-100 rounded-[2.5rem] p-8">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-400 mb-3">First Purchase</p>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Welcome Pack — 1 Credit</h3>
                        <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                            Your first purchase is for 1 credit, enough for one 60-minute session (or two 30-minute sessions).
                        </p>
                        <p class="mt-4 text-3xl font-black text-indigo-600">
                            ${{ number_format($ratePerCredit, 2) }}
                        </p>
                    </div>
                @else
                    {{-- REPEAT BUYER: choose pack --}}
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Choose a Credit Pack</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach([4, 6, 8, 10] as $pack)
                                <label class="cursor-pointer">
                                    <input type="radio" name="_pack_display" value="{{ $pack }}"
                                           x-model.number="selectedPack" class="sr-only">
                                    <div class="rounded-[2rem] border-2 p-6 text-center transition-all"
                                         :class="selectedPack === {{ $pack }}
                                             ? 'border-indigo-500 bg-indigo-50 shadow-lg shadow-indigo-100'
                                             : 'border-slate-100 bg-white hover:border-slate-300'">
                                        <p class="text-4xl font-black"
                                           :class="selectedPack === {{ $pack }} ? 'text-indigo-600' : 'text-slate-700'">
                                            {{ $pack }}
                                        </p>
                                        <p class="text-[9px] font-black uppercase tracking-widest mt-1 text-slate-400">Credits</p>
                                        <p class="text-sm font-black mt-3"
                                           :class="selectedPack === {{ $pack }} ? 'text-indigo-600' : 'text-slate-500'">
                                            ${{ number_format($pack * $ratePerCredit, 2) }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- PAYMENT METHOD GRID --}}
                <div class="space-y-4">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Choose Payment Method</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        {{-- STRIPE --}}
                        <form action="{{ route('customer.credits.purchase') }}" method="POST">
                            @csrf
                            <input type="hidden" name="payment_method" value="stripe">
                            @if(!$isFirstPurchase)
                                <input type="hidden" name="credits" :value="selectedPack" x-bind:value="selectedPack">
                            @endif
                            <button type="submit"
                                    class="w-full text-left p-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 transition-all duration-300 group">
                                <div class="w-12 h-12 bg-indigo-600 text-white rounded-[1.2rem] flex items-center justify-center text-2xl mb-6 shadow-lg shadow-indigo-200">
                                    <i class="ti-credit-card"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-900 tracking-tight">Card / Apple Pay</h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">via Stripe &nbsp;<i class="ti-arrow-right group-hover:translate-x-1 transition-transform inline-block"></i></p>
                            </button>
                        </form>

                        {{-- VENMO --}}
                        <a href="{{ $paymentMethods['venmo']['web_url'] }}" target="_blank" rel="noopener noreferrer"
                           class="block text-left p-8 bg-[#3d95ce] rounded-[2.5rem] text-white shadow-xl shadow-sky-200/50 hover:-translate-y-1 transition-all duration-300 group">
                            <div class="w-12 h-12 bg-white/20 text-white rounded-[1.2rem] flex items-center justify-center text-2xl mb-6">
                                <i class="ti-mobile"></i>
                            </div>
                            <h4 class="text-lg font-black tracking-tight">Venmo</h4>
                            <p class="text-[10px] font-bold text-sky-100/60 uppercase tracking-widest mt-2">Open App &nbsp;<i class="ti-arrow-right group-hover:translate-x-1 transition-transform inline-block"></i></p>
                        </a>

                        {{-- ZELLE --}}
                        <form action="{{ route('customer.credits.purchase') }}" method="POST">
                            @csrf
                            <input type="hidden" name="payment_method" value="zelle">
                            @if(!$isFirstPurchase)
                                <input type="hidden" name="credits" :value="selectedPack" x-bind:value="selectedPack">
                            @endif
                            <button type="submit"
                                    class="w-full text-left p-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-1 transition-all duration-300 group">
                                <div class="w-12 h-12 bg-purple-600 text-white rounded-[1.2rem] flex items-center justify-center text-2xl mb-6 shadow-lg shadow-purple-200">
                                    <i class="ti-shift-right"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-900 tracking-tight">Zelle</h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Bank Transfer &nbsp;<i class="ti-arrow-right group-hover:translate-x-1 transition-transform inline-block"></i></p>
                            </button>
                        </form>

                    </div>
                </div>

                {{-- HOW CREDITS WORK --}}
                <div class="bg-slate-50 border border-slate-100 rounded-[2.5rem] p-8 space-y-4">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">How Credits Work</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        @foreach(['0.5' => '30 min', '1' => '60 min', '1.5' => '90 min', '2' => '120 min'] as $cr => $dur)
                            <div class="bg-white rounded-2xl border border-slate-100 p-4">
                                <p class="text-2xl font-black text-indigo-600">{{ $cr }}</p>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ $dur }} session</p>
                                @if($ratePerCredit)
                                    <p class="text-[9px] font-bold text-slate-500 mt-1">= ${{ number_format($cr * $ratePerCredit, 2) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-400 text-center pt-2">
                        Credits are deducted automatically when your tutor submits a session report.
                    </p>
                </div>

            </div>
        @endif

    </div>
</x-app-layout>

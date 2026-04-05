<x-app-layout>
    <x-slot name="header_title">Financial Hub</x-slot>

    <div class="max-w-5xl mx-auto space-y-12">

        @if(session('payment_instructions'))
            <div class="p-8 rounded-[2.5rem] bg-emerald-50 border border-emerald-100 shadow-sm">
                <p class="text-sm font-black text-emerald-900">Payment instructions for {{ session('payment_instructions.method') }}:</p>
                <p class="mt-4 text-sm text-slate-600">Send <span class="font-black text-slate-900">${{ session('payment_instructions.amount') }}</span> to <span class="font-black text-slate-900">{{ session('payment_instructions.contact') }}</span>.</p>
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
                        <div x-data="{ showQr: false, checkoutUrl: '', qrUrl: '', loading: false }" class="p-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 flex flex-col min-h-[260px]">

                            {{-- DEFAULT VIEW --}}
                            <div x-show="!showQr" class="flex flex-col h-full">
                                <div class="w-12 h-12 bg-indigo-600 text-white rounded-[1.2rem] flex items-center justify-center text-2xl mb-6 shadow-lg shadow-indigo-200">
                                    <i class="ti-credit-card"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-900 tracking-tight">Card / Apple Pay</h4>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">via Stripe</p>
                                <div class="flex gap-3 mt-auto pt-6">
                                    <form action="{{ route('customer.credits.purchase') }}" method="POST" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="stripe">
                                        @if(!$isFirstPurchase)
                                            <input type="hidden" name="credits" :value="selectedPack" x-bind:value="selectedPack">
                                        @endif
                                        <button type="submit"
                                                class="w-full py-3 bg-indigo-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-colors">
                                            Pay Now
                                        </button>
                                    </form>
                                    <button type="button" :disabled="loading"
                                            @click="
                                                loading = true;
                                                fetch('{{ route('customer.credits.stripe-url') }}', {
                                                    method: 'POST',
                                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                    body: JSON.stringify({ credits: {{ $isFirstPurchase ? 1 : 'null' }} ?? selectedPack })
                                                })
                                                .then(r => r.json())
                                                .then(data => {
                                                    checkoutUrl = data.url;
                                                    qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data.url);
                                                    showQr = true;
                                                    loading = false;
                                                })
                                                .catch(() => { loading = false; });
                                            "
                                            class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-200 transition-colors disabled:opacity-50">
                                        <span x-show="!loading">Show QR</span>
                                        <span x-show="loading">...</span>
                                    </button>
                                </div>
                            </div>

                            {{-- QR VIEW --}}
                            <template x-if="showQr">
                                <div class="flex flex-col items-center text-center">
                                    <img :src="qrUrl" alt="Stripe QR" class="w-40 h-40 rounded-2xl border border-slate-100 p-2" />
                                    <p class="text-[9px] text-slate-400 uppercase tracking-widest mt-2">Scan on your phone</p>
                                    <a :href="checkoutUrl"
                                       class="mt-3 w-full py-3 bg-indigo-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest text-center hover:bg-indigo-700 transition-colors">
                                        <i class="ti-credit-card mr-1"></i> Go to Checkout
                                    </a>
                                    <button type="button" @click="showQr = false; qrUrl = ''; checkoutUrl = '';"
                                            class="mt-3 text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-700 transition-colors">
                                        &larr; Back
                                    </button>
                                </div>
                            </template>

                        </div>

                        {{-- VENMO --}}
                        <div x-data="{ showQr: false }"
                             class="p-8 bg-[#3d95ce] rounded-[2.5rem] text-white shadow-xl shadow-sky-200/50 flex flex-col min-h-[260px]">

                            {{-- DEFAULT VIEW --}}
                            <div x-show="!showQr" class="flex flex-col h-full">
                                <div class="w-12 h-12 bg-white/20 text-white rounded-[1.2rem] flex items-center justify-center text-2xl mb-6">
                                    <i class="ti-mobile"></i>
                                </div>
                                <h4 class="text-lg font-black tracking-tight">Venmo</h4>
                                <p class="text-[10px] text-sky-100/60 uppercase tracking-widest mt-1">{{ $paymentMethods['venmo']['username'] }}</p>
                                <div class="flex gap-3 mt-auto pt-6">
                                    <a href="{{ $paymentMethods['venmo']['web_url'] }}" target="_blank" rel="noopener noreferrer"
                                       class="flex-1 py-3 bg-white text-[#3d95ce] rounded-xl text-[9px] font-black uppercase tracking-widest text-center hover:bg-sky-50 transition-colors">
                                        Open App
                                    </a>
                                    <button type="button" @click="showQr = true"
                                            class="flex-1 py-3 bg-white/20 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-white/30 transition-colors">
                                        Show QR
                                    </button>
                                </div>
                            </div>

                            {{-- QR VIEW: x-if removes the img from DOM until needed, preventing an empty-data fetch --}}
                            <template x-if="showQr">
                                <div class="flex flex-col items-center text-center">
                                    <img :src="window.PaymentConfig.venmoQrUrl(selectedPack)"
                                         alt="Venmo QR" class="w-40 h-40 rounded-2xl bg-white p-2" />
                                    <p class="text-sm font-black mt-4">{{ $paymentMethods['venmo']['username'] }}</p>
                                    <p class="text-[9px] text-sky-100/60 uppercase tracking-widest mt-1">Scan with Venmo app</p>
                                    <button type="button" @click="showQr = false"
                                            class="mt-4 text-[9px] font-black uppercase tracking-widest text-sky-100/60 hover:text-white transition-colors">
                                        &larr; Back
                                    </button>
                                </div>
                            </template>

                        </div>

                        {{-- ZELLE --}}
                        <div x-data="{ showQr: false }"
                             class="p-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 flex flex-col min-h-[260px]">

                            {{-- DEFAULT VIEW --}}
                            <div x-show="!showQr" class="flex flex-col h-full">
                                <div class="w-12 h-12 bg-purple-600 text-white rounded-[1.2rem] flex items-center justify-center text-2xl mb-6 shadow-lg shadow-purple-200">
                                    <i class="ti-shift-right"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-900 tracking-tight">Zelle</h4>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">{{ $paymentMethods['zelle']['phone'] }}</p>
                                <div class="flex gap-3 mt-auto pt-6">
                                    <form action="{{ route('customer.credits.purchase') }}" method="POST" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="zelle">
                                        @if(!$isFirstPurchase)
                                            <input type="hidden" name="credits" :value="selectedPack" x-bind:value="selectedPack">
                                        @endif
                                        <button type="submit"
                                                class="w-full py-3 bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-black transition-colors">
                                            Get Info
                                        </button>
                                    </form>
                                    <button type="button" @click="showQr = true"
                                            class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">
                                        Show QR
                                    </button>
                                </div>
                            </div>

                            {{-- QR VIEW: x-if removes the img from DOM until needed, preventing an early fetch --}}
                            <template x-if="showQr">
                                <div class="flex flex-col items-center text-center">
                                    <img :src="window.PaymentConfig.zelleQrUrl"
                                         alt="Zelle QR" class="w-40 h-40 rounded-2xl border border-slate-100 p-2" />
                                    <p class="text-sm font-black text-slate-900 mt-4">{{ $paymentMethods['zelle']['phone'] }}</p>
                                    <p class="text-[9px] text-slate-400 uppercase tracking-widest mt-1">Scan to get contact</p>
                                    <button type="button" @click="showQr = false"
                                            class="mt-4 text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-700 transition-colors">
                                        &larr; Back
                                    </button>
                                </div>
                            </template>

                        </div>

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
@push('scripts')
<script>
window.PaymentConfig = {
    zelleQrUrl: @json($paymentMethods['zelle']['qr_url']),
    venmoRecipient: @json(ltrim($paymentMethods['venmo']['username'], '@')),
    venmoNote: @json($paymentMethods['venmo']['note']),
    venmoRate: {{ (float)($ratePerCredit ?? 0) }},
    venmoQrUrl: function(pack) {
        var deep = 'venmo://paycharge?txn=pay&recipients=' + this.venmoRecipient
            + '&note=' + encodeURIComponent(this.venmoNote)
            + '&amount=' + (pack * this.venmoRate).toFixed(2);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&charset-source=UTF-8&data=' + encodeURIComponent(deep);
    }
};
</script>
@endpush
</x-app-layout>

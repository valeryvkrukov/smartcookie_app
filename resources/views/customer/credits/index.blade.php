<x-app-layout>
    <x-slot name="header_title">Financial Hub</x-slot>

    <div class="max-w-5xl mx-auto space-y-12">
        @if(session('payment_instructions'))
            <div class="p-6 rounded-[2.5rem] bg-emerald-50 border border-emerald-100 shadow-sm">
                <p class="text-sm font-black text-emerald-900">Payment instructions for {{ session('payment_instructions.method') }}:</p>
                <p class="mt-4 text-sm text-slate-600">Send your transfer to <span class="font-black text-slate-900">{{ session('payment_instructions.email') }}</span>.</p>
                <p class="mt-2 text-sm text-slate-600">Use note: <span class="font-black text-slate-900">{{ session('payment_instructions.note') }}</span>.</p>
                <p class="mt-3 text-[10px] uppercase tracking-[0.3em] text-slate-400">Credits will be added after payment confirmation.</p>
            </div>
        @endif

        <!-- MAIN CREDIT CARD -->
        <div class="relative overflow-hidden bg-[#1A1A19] rounded-[3.5rem] p-12 shadow-2xl shadow-indigo-500/20 group">
            <!-- Decorative Gradients -->
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-600/20 blur-[100px] rounded-full group-hover:bg-indigo-600/30 transition-all duration-700"></div>
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-emerald-600/10 blur-[100px] rounded-full group-hover:bg-emerald-600/20 transition-all duration-700"></div>

            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="text-center md:text-left">
                    <p class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-500 mb-2">Total Credit Balance</p>
                    <h2 class="text-7xl font-black text-white tracking-tighter drop-shadow-2xl">
                        <span class="text-indigo-400">$</span>{{ number_format($balance, 2) }}
                    </h2>
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

        <!-- PAYMENT METHODS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- STRIPE (Credit Card / Apple Pay) -->
            <form action="{{ route('customer.credits.purchase') }}" method="POST" class="h-full">
                @csrf
                <input type="hidden" name="payment_method" value="stripe">
                <button type="submit" class="w-full h-full text-left p-10 bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500"></div>
                    
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-indigo-600 text-white rounded-[1.5rem] flex items-center justify-center text-3xl mb-8 shadow-lg shadow-indigo-200">
                            <i class="ti-credit-card"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Stripe</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2 leading-relaxed">
                            Cards, Apple Pay,<br>Google Pay
                        </p>
                        <div class="mt-8 flex flex-col gap-2 text-[9px] font-black text-indigo-600 uppercase tracking-widest">
                            <span class="flex items-center">Stripe payment <i class="ti-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i></span>
                        </div>
                    </div>
                </button>
            </form>

            <!-- VENMO (Mobile First) -->
            <a href="{{ $paymentMethods['venmo']['web_url'] }}" 
               class="text-left p-10 bg-[#3d95ce] rounded-[3rem] text-white shadow-xl shadow-sky-200/50 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden" target="_blank" rel="noopener noreferrer">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500"></div>
                
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-md text-white rounded-[1.5rem] flex items-center justify-center text-4xl mb-8 shadow-inner">
                        <i class="ti-mobile"></i>
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Venmo</h3>
                    <p class="text-[10px] font-bold text-sky-100/60 uppercase tracking-widest mt-2 leading-relaxed">
                        Direct Mobile<br>Transfer
                    </p>
                    <div class="mt-8 flex items-center text-[9px] font-black text-white uppercase tracking-widest">
                        Open App <i class="ti-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </div>
                </div>
            </a>

            <!-- ZELLE (Instructional) -->
            <button type="button" @click="$dispatch('open-modal', { type: 'zelle-info', title: 'Payment Instruction' })"
                    class="text-left p-10 bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/50 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-50 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500"></div>
                
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-purple-600 text-white rounded-[1.5rem] flex items-center justify-center text-3xl mb-8 shadow-lg shadow-purple-200">
                        <i class="ti-shift-right"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 tracking-tight">Zelle</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2 leading-relaxed">
                        Bank to Bank<br>Direct Transfer
                    </p>
                    <div class="mt-8 flex items-center text-[9px] font-black text-purple-600 uppercase tracking-widest">
                        View QR Code <i class="ti-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </div>
                </div>
            </button>

        </div>

        <!-- FOOTER INFO -->
        <p class="text-center text-[9px] font-bold text-slate-400 uppercase tracking-[0.3em]">
            Credits are applied instantly to your account.
        </p>
    </div>
</x-app-layout>

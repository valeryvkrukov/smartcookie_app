<x-app-layout>
    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-800">Credits & Billings</h3>
        </div>
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-4">Your Credit Balance: 
                <span class="text-indigo-600">{{ $credit->credit_balance }} Credits</span>
            </h2>

            {{-- ── Purchase section: locked if credit cost not yet configured --}}
            <div class="{{ $isLocked ? 'opacity-50 pointer-events-none' : '' }}">
                @if($isLocked)
                    <p class="text-red-500 mb-4 font-bold italic">
                        Section locked: An administrator must define your credit rate before you can purchase.
                    </p>
                @endif

                <h3 class="text-lg font-semibold mb-2">Purchase Credits</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($availablePacks as $pack)
                        <button class="bg-indigo-600 text-white p-4 rounded-lg hover:bg-indigo-700 transition">
                            Buy {{ $pack }} {{ \Illuminate\Support\Str::plural('Credit', $pack) }}<br>
                            <span class="text-xs">
                                Total: ${{ number_format($pack * $credit->dollar_cost_per_credit, 2) }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-10">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Usage History</h3>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="p-2 text-left">Date</th>
                            <th class="p-2 text-left">Student</th>
                            <th class="p-2 text-right">Credits Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $log)
                            <tr class="border-b">
                                <td class="p-2">{{ $log->created_at->format('M d, Y') }}</td>
                                <td class="p-2">{{ $log->student->first_name }}</td>
                                <td class="p-2 text-right text-red-600 font-bold">-{{ $log->credits_spent }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-800">{{ $request->agreement->name }}</h3>
        </div>
        <div class="p-6">
            <!-- Show PDF file contents -->
            <div class="mb-6 border rounded overflow-hidden">
                <iframe src="{{ asset('storage/' . $request->agreement->pdf_path) }}" 
                        width="100%" height="500px" style="border: none;"></iframe>
            </div>

            @if($request->status === 'Awaiting signature')
                <form action="{{ route('agreements.sign', $request->id) }}" method="POST" class="space-y-4 border-t pt-6">
                    @csrf
                    <div class="flex items-start">
                        <input type="checkbox" name="agree" id="agree" required class="mt-1 rounded">
                        <label for="agree" class="ml-2 text-sm text-gray-700">
                            I agree to the terms of this agreement.
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="full_name" value="Type in your full name *" />
                            <x-text-input name="full_name" class="block mt-1 w-full" required />
                        </div>
                        <div>
                            <x-input-label for="signed_date" value="Today's Date *" />
                            <x-text-input type="date" name="signed_date" class="block mt-1 w-full" value="{{ date('Y-m-d') }}" required />
                        </div>
                    </div>

                    <x-primary-button class="w-full justify-center">Submit Agreement</x-primary-button>
                </form>
            @else
                <div class="bg-green-100 p-4 rounded text-green-700 font-bold">
                    Signed on {{ $request->signed_at?->format('M d, Y H:i') }} by {{ $request->signed_full_name }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

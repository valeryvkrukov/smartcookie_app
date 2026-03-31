<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold mb-6">Assigned Tutors</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($students as $student)
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-indigo-600 font-bold border-b pb-2 mb-4">
                            Tutors for {{ $student->first_name }}
                        </h3>

                        @forelse($student->assignedTutors as $tutor)
                            <div class="flex items-start space-x-4 mb-6 last:mb-0">
                                <!-- Tutor photo -->
                                <img src="{{ $tutor->photo ? asset('storage/'.$tutor->photo) : asset('images/generic-avatar.png') }}" 
                                     class="w-16 h-16 rounded-full object-cover border">
                                
                                <div>
                                    <h4 class="font-bold text-lg">{{ $tutor->full_name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $tutor->email }}</p>
                                    <p class="text-sm text-gray-600">{{ $tutor->phone }}</p>
                                    <p class="mt-2 text-gray-700 italic text-sm">"{{ $tutor->blurb }}"</p>
                                </div>
                            </div>
                        @empty
                            <div class="flex items-center space-x-4 opacity-50">
                                <img src="{{ asset('images/generic-avatar.png') }}" class="w-16 h-16 rounded-full grayscale">
                                <div>
                                    <h4 class="font-bold text-gray-500">Tutor assignment pending</h4>
                                    <p class="text-sm text-gray-400">We are currently matching a tutor for {{ $student->first_name }}.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

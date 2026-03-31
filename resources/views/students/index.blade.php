<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Dashboard >
        </a>
        Students
    </x-slot>
    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
        <div class="p-6 flex border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-800">My Students</h3>
            <div class="ml-auto">
                <a href="{{ route('customer.students.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded shadow">
                    + Add New Student
                </a>
            </div>
        </div>
        
        <div class="p-6">
            @foreach($students as $student)
            <div class="p-4 border-b">
                <p class="font-bold">{{ $student->first_name }}</p>
                <p class="text-sm text-gray-500">{{ $student->student_school }}</p>

                <!-- Edit -->
                <a href="{{ route('customer.students.edit', $student->id) }}" class="text-indigo-600 hover:text-indigo-900">
                    Edit
                </a>
                <!-- Delete -->
                <form action="{{ route('customer.students.destroy', $student->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this student?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-900 ml-4">
                        Remove
                    </button>
                </form>
            </div>
            @endforeach

            @if($students->isEmpty())
            <p>No students found. Add your first student!</p>
            @endif

        </div>
    </div>
</x-app-layout>
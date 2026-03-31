<x-app-layout>
    <x-slot name="header_title">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition">
            Dashboard >
        </a>
        Add Student
    </x-slot>

    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-800">Add Student</h3>
        </div>
        <div class="p-6">
            <div class="mt-4">
                <x-input-label for="tutoring_goals" :value="__('Student Tutoring Goals')" />
                <textarea 
                    id="tutoring_goals" 
                    name="tutoring_goals" 
                    rows="4" 
                    placeholder="Tell us a little about what you want to get out of tutoring!" 
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:ring-indigo-500 focus:border-indigo-500"
                >{{ old('tutoring_goals', $student->tutoring_goals) }}</textarea>
            </div>
        </div>
    </div>
</x-app-layout>
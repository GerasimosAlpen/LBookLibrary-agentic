@extends('layouts.app')

@section('title', 'Edit: ' . $category->name)

@section('content')
<div class="max-w-xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-white">Edit Category</h1>
        <p class="text-slate-400 mt-1 text-sm">Update the name for <span class="text-slate-300">{{ $category->name }}</span>.</p>
    </div>

    <form action="{{ route('categories.update', $category->id) }}" method="POST"
          class="bg-slate-800/50 border border-slate-700/40 rounded-2xl p-6 sm:p-8 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-slate-300 mb-1.5">Category Name <span class="text-red-400">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required
                   class="w-full bg-slate-900/60 border {{ $errors->has('name') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition" />
            @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                Save Changes
            </button>
            <a href="{{ route('categories.show', $category->id) }}" class="text-sm text-slate-400 hover:text-slate-200 transition px-4 py-2.5">Cancel</a>
        </div>
    </form>
</div>
@endsection

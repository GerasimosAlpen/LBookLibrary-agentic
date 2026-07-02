@extends('layouts.app')

@section('title', 'Edit: ' . $author->name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-white">Edit Author</h1>
        <p class="text-slate-400 mt-1 text-sm">Update profile for <span class="text-slate-300">{{ $author->name }}</span>.</p>
    </div>

    <form action="{{ route('authors.update', $author->id) }}" method="POST"
          class="bg-slate-800/50 border border-slate-700/40 rounded-2xl p-6 sm:p-8 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-slate-300 mb-1.5">Name <span class="text-red-400">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $author->name) }}" required
                   class="w-full bg-slate-900/60 border {{ $errors->has('name') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition" />
            @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="bio" class="block text-sm font-medium text-slate-300 mb-1.5">Biography</label>
            <textarea id="bio" name="bio" rows="5"
                      class="w-full bg-slate-900/60 border {{ $errors->has('bio') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition resize-none">{{ old('bio', $author->bio) }}</textarea>
            @error('bio') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                Save Changes
            </button>
            <a href="{{ route('authors.show', $author->id) }}" class="text-sm text-slate-400 hover:text-slate-200 transition px-4 py-2.5">Cancel</a>
        </div>
    </form>
</div>
@endsection

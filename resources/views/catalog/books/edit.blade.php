@extends('layouts.app')

@section('title', 'Edit: ' . $book->title)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-white">Edit Book</h1>
        <p class="text-slate-400 mt-1 text-sm">Update the details for <span class="text-slate-300">{{ $book->title }}</span>.</p>
    </div>

    <form action="{{ route('books.update', $book->id) }}" method="POST"
          class="bg-slate-800/50 border border-slate-700/40 rounded-2xl p-6 sm:p-8 space-y-5">
        @csrf
        @method('PUT')

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-medium text-slate-300 mb-1.5">Title <span class="text-red-400">*</span></label>
            <input id="title" name="title" type="text" value="{{ old('title', $book->title) }}" required
                   class="w-full bg-slate-900/60 border {{ $errors->has('title') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition" />
            @error('title') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Author --}}
        <div>
            <label for="author_id" class="block text-sm font-medium text-slate-300 mb-1.5">Author <span class="text-red-400">*</span></label>
            <select id="author_id" name="author_id" required
                    class="w-full bg-slate-900/60 border {{ $errors->has('author_id') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition">
                <option value="">Select an author…</option>
                @foreach($authors as $author)
                    <option value="{{ $author->id }}" {{ old('author_id', $book->author_id) == $author->id ? 'selected' : '' }}>
                        {{ $author->name }}
                    </option>
                @endforeach
            </select>
            @error('author_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- ISBN & Year --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="isbn" class="block text-sm font-medium text-slate-300 mb-1.5">ISBN</label>
                <input id="isbn" name="isbn" type="text" value="{{ old('isbn', $book->isbn) }}"
                       class="w-full bg-slate-900/60 border {{ $errors->has('isbn') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition font-mono" />
                @error('isbn') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="published_year" class="block text-sm font-medium text-slate-300 mb-1.5">Publication Year</label>
                <input id="published_year" name="published_year" type="number"
                       value="{{ old('published_year', $book->published_year) }}"
                       min="1000" max="{{ date('Y') + 1 }}"
                       class="w-full bg-slate-900/60 border {{ $errors->has('published_year') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition" />
                @error('published_year') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
            <textarea id="description" name="description" rows="4"
                      class="w-full bg-slate-900/60 border {{ $errors->has('description') ? 'border-red-500/60' : 'border-slate-600/50' }} text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition resize-none">{{ old('description', $book->description) }}</textarea>
            @error('description') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Categories --}}
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Categories</label>
            @php $selectedCats = old('category_ids', $book->categories->pluck('id')->toArray()); @endphp
            <div class="flex flex-wrap gap-2">
                @foreach($categories as $cat)
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                               {{ in_array($cat->id, $selectedCats) ? 'checked' : '' }}
                               class="rounded border-slate-600 bg-slate-900 text-indigo-600 focus:ring-indigo-500/30" />
                        <span class="text-sm text-slate-300 hover:text-white transition">{{ $cat->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('category_ids') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                Save Changes
            </button>
            <a href="{{ route('books.show', $book->id) }}" class="text-sm text-slate-400 hover:text-slate-200 transition px-4 py-2.5">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Books Catalog')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Books Catalog</h1>
            <p class="text-slate-400 mt-1">Browse {{ $books->total() }} {{ Str::plural('book', $books->total()) }}</p>
        </div>
        @auth
            @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                <a href="{{ route('books.create') }}"
                   class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Book
                </a>
            @endif
        @endauth
    </div>

    {{-- Search & Filter --}}
    <form id="catalog-filter-form" method="GET" action="{{ route('books.index') }}"
          class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-4 sm:p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <label for="search" class="sr-only">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="search" name="search" type="search"
                           value="{{ request('search') }}"
                           placeholder="Search by title, ISBN or author…"
                           class="w-full bg-slate-900/60 border border-slate-600/50 text-slate-200 placeholder-slate-500 text-sm rounded-xl pl-9 pr-4 py-2.5 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition" />
                </div>
            </div>

            {{-- Category Filter --}}
            <div>
                <label for="category" class="sr-only">Category</label>
                <select id="category" name="category"
                        class="w-full bg-slate-900/60 border border-slate-600/50 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/50 transition">
                    <option value="" class="bg-slate-900 text-slate-100">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" class="bg-slate-900 text-slate-100" {{ request('category') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Sort --}}
            <div class="flex gap-2">
                <select name="sort_by"
                        class="flex-1 bg-slate-900/60 border border-slate-600/50 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 transition">
                    <option value="title" class="bg-slate-900 text-slate-100" {{ request('sort_by','title') === 'title' ? 'selected' : '' }}>Title</option>
                    <option value="published_year" class="bg-slate-900 text-slate-100" {{ request('sort_by') === 'published_year' ? 'selected' : '' }}>Year</option>
                </select>
                <select name="sort_dir"
                        class="bg-slate-900/60 border border-slate-600/50 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 transition">
                    <option value="asc" class="bg-slate-900 text-slate-100" {{ request('sort_dir','asc') === 'asc' ? 'selected' : '' }}>↑ Asc</option>
                    <option value="desc" class="bg-slate-900 text-slate-100" {{ request('sort_dir') === 'desc' ? 'selected' : '' }}>↓ Desc</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2 rounded-xl transition-all">
                Search
            </button>
            @if(request()->hasAny(['search','category','sort_by','sort_dir']))
                <a href="{{ route('books.index') }}" class="text-sm text-slate-400 hover:text-slate-200 transition">
                    Clear filters
                </a>
            @endif
        </div>
    </form>

    {{-- Grid --}}
    @if($books->isEmpty())
        <div class="text-center py-20">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-800/60 border border-slate-700/50 mb-4">
                <svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <p class="text-slate-400 text-lg font-medium">No books found</p>
            <p class="text-slate-600 text-sm mt-1">Try adjusting your search or filter criteria.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($books as $book)
                <a href="{{ route('books.show', $book->id) }}"
                   class="group bg-slate-800/40 border border-slate-700/40 rounded-2xl p-5 hover:border-indigo-500/40 hover:bg-slate-800/70 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-indigo-500/5 flex flex-col gap-3">

                    {{-- Year badge --}}
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500 bg-slate-900/60 px-2 py-0.5 rounded-lg border border-slate-700/50">
                            {{ $book->published_year ?? 'N/A' }}
                        </span>
                        <svg class="w-4 h-4 text-slate-600 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>

                    {{-- Title --}}
                    <div class="flex-1">
                        <h2 class="font-semibold text-white text-sm leading-snug group-hover:text-indigo-300 transition-colors line-clamp-2">
                            {{ $book->title }}
                        </h2>
                        <p class="text-slate-500 text-xs mt-1">by {{ $book->author->name ?? '—' }}</p>
                    </div>

                    {{-- ISBN --}}
                    @if($book->isbn)
                        <p class="text-xs text-slate-600 font-mono truncate">{{ $book->isbn }}</p>
                    @endif

                    {{-- Categories --}}
                    @if($book->categories->isNotEmpty())
                        <div class="flex flex-wrap gap-1">
                            @foreach($book->categories->take(3) as $cat)
                                <span class="text-xs bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 px-2 py-0.5 rounded-lg">
                                    {{ $cat->name }}
                                </span>
                            @endforeach
                            @if($book->categories->count() > 3)
                                <span class="text-xs text-slate-600">+{{ $book->categories->count() - 3 }}</span>
                            @endif
                        </div>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($books->hasPages())
            <div class="mt-6">
                {{ $books->links() }}
            </div>
        @endif
    @endif

</div>
@endsection

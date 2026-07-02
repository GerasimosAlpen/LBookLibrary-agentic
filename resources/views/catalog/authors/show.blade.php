@extends('layouts.app')

@section('title', $author->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('authors.index') }}" class="hover:text-slate-300 transition">Authors</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-400">{{ $author->name }}</span>
    </nav>

    {{-- Author Header --}}
    <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row items-start gap-6">
            <div class="shrink-0 w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-600/20 to-purple-600/20 border border-indigo-500/20 flex items-center justify-center">
                <span class="text-3xl font-bold text-indigo-300">{{ strtoupper(substr($author->name, 0, 1)) }}</span>
            </div>
            <div class="flex-1">
                <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $author->name }}</h1>
                <p class="text-slate-500 text-sm mt-1">{{ $books->total() }} {{ Str::plural('book', $books->total()) }} in the catalog</p>

                @if($author->bio)
                    <div class="mt-4 pt-4 border-t border-slate-700/40">
                        <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Biography</h2>
                        <p class="text-slate-300 leading-relaxed whitespace-pre-line">{{ $author->bio }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Admin Actions --}}
    @auth
        @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('authors.edit', $author->id) }}"
                   class="inline-flex items-center gap-2 bg-amber-600/20 hover:bg-amber-600/30 border border-amber-500/30 text-amber-300 text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                <form action="{{ route('authors.destroy', $author->id) }}" method="POST"
                      onsubmit="return confirm('Delete this author?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-red-600/20 hover:bg-red-600/30 border border-red-500/30 text-red-300 text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        @endif
    @endauth

    {{-- Books by Author --}}
    <div>
        <h2 class="text-lg font-semibold text-white mb-4">Books by {{ $author->name }}</h2>

        @if($books->isEmpty())
            <div class="text-center py-12 bg-slate-800/30 border border-slate-700/30 rounded-2xl">
                <p class="text-slate-500">No books found for this author.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($books as $book)
                    <a href="{{ route('books.show', $book->id) }}"
                       class="group bg-slate-800/40 border border-slate-700/40 rounded-xl p-4 hover:border-indigo-500/40 hover:bg-slate-800/70 transition-all flex items-start gap-3">
                        <div class="shrink-0 w-10 h-10 rounded-lg bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center mt-0.5">
                            <svg class="w-5 h-5 text-indigo-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm text-white group-hover:text-indigo-300 transition-colors leading-snug line-clamp-2">
                                {{ $book->title }}
                            </h3>
                            <p class="text-slate-600 text-xs mt-0.5">{{ $book->published_year ?? 'N/A' }}</p>
                            @if($book->categories->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach($book->categories->take(2) as $cat)
                                        <span class="text-xs bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 px-1.5 py-0.5 rounded">{{ $cat->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            @if($books->hasPages())
                <div class="mt-4">{{ $books->links() }}</div>
            @endif
        @endif
    </div>

    <a href="{{ route('authors.index') }}" class="text-sm text-slate-500 hover:text-slate-300 transition inline-flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Authors
    </a>

</div>
@endsection

@extends('layouts.app')

@section('title', $book->title)

@section('content')
<div class="max-w-4xl mx-auto space-y-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('books.index') }}" class="hover:text-slate-300 transition">Books</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-400 truncate max-w-xs">{{ $book->title }}</span>
    </nav>

    {{-- Book Header --}}
    <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-start gap-6">
            {{-- Book Icon --}}
            <div class="shrink-0 w-20 h-28 bg-gradient-to-br from-indigo-600/20 to-purple-600/20 border border-indigo-500/20 rounded-xl flex items-center justify-center">
                <svg class="w-10 h-10 text-indigo-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold text-white leading-snug">{{ $book->title }}</h1>

                <a href="{{ route('authors.show', $book->author->id) }}"
                   class="inline-block mt-2 text-indigo-400 hover:text-indigo-300 transition text-sm font-medium">
                    by {{ $book->author->name ?? '—' }}
                </a>

                <div class="flex flex-wrap gap-3 mt-4">
                    @if($book->published_year)
                        <span class="flex items-center gap-1.5 text-sm text-slate-400 bg-slate-900/40 px-3 py-1 rounded-lg border border-slate-700/40">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $book->published_year }}
                        </span>
                    @endif
                    @if($book->isbn)
                        <span class="flex items-center gap-1.5 text-sm text-slate-400 bg-slate-900/40 px-3 py-1 rounded-lg border border-slate-700/40 font-mono">
                            ISBN: {{ $book->isbn }}
                        </span>
                    @endif
                </div>

                {{-- Categories --}}
                @if($book->categories->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($book->categories as $cat)
                            <a href="{{ route('categories.show', $cat->id) }}"
                               class="text-xs bg-indigo-600/10 hover:bg-indigo-600/20 border border-indigo-500/20 text-indigo-400 px-3 py-1 rounded-full transition">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Description --}}
        @if($book->description)
            <div class="mt-6 pt-6 border-t border-slate-700/40">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-3">Description</h2>
                <p class="text-slate-300 leading-relaxed whitespace-pre-line">{{ $book->description }}</p>
            </div>
        @endif
    </div>

    {{-- Admin Actions --}}
    @auth
        @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('books.edit', $book->id) }}"
                   class="inline-flex items-center gap-2 bg-amber-600/20 hover:bg-amber-600/30 border border-amber-500/30 text-amber-300 hover:text-amber-200 text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Book
                </a>
                <form action="{{ route('books.destroy', $book->id) }}" method="POST"
                      onsubmit="return confirm('Delete this book? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-red-600/20 hover:bg-red-600/30 border border-red-500/30 text-red-300 hover:text-red-200 text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        @endif
    @endauth

    <div>
        <a href="{{ route('books.index') }}" class="text-sm text-slate-500 hover:text-slate-300 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to catalog
        </a>
    </div>

</div>
@endsection

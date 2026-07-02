@extends('layouts.app')

@section('title', 'Authors')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Authors</h1>
            <p class="text-slate-400 mt-1">{{ $authors->total() }} {{ Str::plural('author', $authors->total()) }} in the catalog</p>
        </div>
        @auth
            @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                <a href="{{ route('authors.create') }}"
                   class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add Author
                </a>
            @endif
        @endauth
    </div>

    @if($authors->isEmpty())
        <div class="text-center py-20">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-800/60 border border-slate-700/50 mb-4">
                <svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <p class="text-slate-400 text-lg font-medium">No authors yet</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($authors as $author)
                <a href="{{ route('authors.show', $author->id) }}"
                   class="group bg-slate-800/40 border border-slate-700/40 rounded-2xl p-5 hover:border-indigo-500/40 hover:bg-slate-800/70 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-indigo-500/5 flex items-center gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600/20 to-purple-600/20 border border-indigo-500/20 flex items-center justify-center">
                        <span class="text-indigo-300 font-bold text-lg">{{ strtoupper(substr($author->name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="font-semibold text-white text-sm group-hover:text-indigo-300 transition-colors truncate">
                            {{ $author->name }}
                        </h2>
                        <p class="text-slate-500 text-xs mt-0.5">
                            {{ $author->books_count }} {{ Str::plural('book', $author->books_count) }}
                        </p>
                    </div>
                    <svg class="w-4 h-4 text-slate-600 group-hover:text-indigo-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endforeach
        </div>

        @if($authors->hasPages())
            <div class="mt-6">{{ $authors->links() }}</div>
        @endif
    @endif

</div>
@endsection

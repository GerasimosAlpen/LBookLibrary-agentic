@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Categories</h1>
            <p class="text-slate-400 mt-1">{{ $categories->total() }} {{ Str::plural('category', $categories->total()) }}</p>
        </div>
        @auth
            @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                <a href="{{ route('categories.create') }}"
                   class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add Category
                </a>
            @endif
        @endauth
    </div>

    @if($categories->isEmpty())
        <div class="text-center py-20">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-800/60 border border-slate-700/50 mb-4">
                <svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <p class="text-slate-400 text-lg font-medium">No categories yet</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            @foreach($categories as $cat)
                <a href="{{ route('categories.show', $cat->id) }}"
                   class="group bg-slate-800/40 border border-slate-700/40 rounded-xl p-4 hover:border-indigo-500/40 hover:bg-slate-800/70 transition-all duration-200 hover:-translate-y-0.5 text-center">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-5 h-5 text-indigo-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <h2 class="font-semibold text-sm text-white group-hover:text-indigo-300 transition-colors truncate">
                        {{ $cat->name }}
                    </h2>
                    <p class="text-slate-500 text-xs mt-1">{{ $cat->books_count }} {{ Str::plural('book', $cat->books_count) }}</p>
                </a>
            @endforeach
        </div>

        @if($categories->hasPages())
            <div class="mt-6">{{ $categories->links() }}</div>
        @endif
    @endif

</div>
@endsection

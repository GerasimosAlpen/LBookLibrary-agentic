@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-md mx-auto bg-slate-900/60 border border-slate-700/50 rounded-2xl p-8 shadow-2xl text-center space-y-6 my-8">
    <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />
        </svg>
    </div>

    <div>
        <h1 class="text-3xl font-bold text-white">Welcome, {{ auth()->user()->name }}!</h1>
        <p class="text-slate-400 text-sm mt-1">You are signed in as <span class="text-indigo-400">{{ auth()->user()->email }}</span></p>
    </div>

    <div class="bg-slate-800/40 border border-slate-700/40 rounded-xl px-4 py-3 flex justify-between items-center text-sm">
        <span class="text-slate-400">Your Role</span>
        <span class="text-indigo-400 font-semibold px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20">{{ auth()->user()->role->value }}</span>
    </div>

    <div class="pt-2">
        <a href="{{ route('books.index') }}" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-5 py-3 rounded-xl transition-all shadow-lg shadow-indigo-600/20">
            Browse Books Catalog
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>
    </div>
</div>
@endsection

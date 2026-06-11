@extends('layouts.auth')

@section('title', 'Dashboard')

@section('content')
<div class="min-h-screen flex flex-col">
    {{-- Nav --}}
    <nav class="border-b border-slate-700/50 bg-slate-900/60 backdrop-blur-xl px-6 py-4">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <span class="font-bold text-white">BookLib</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('auth.password') }}" class="text-sm text-slate-400 hover:text-slate-200 transition">Update Password</a>
                <form action="{{ route('auth.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm bg-red-600/20 hover:bg-red-600/40 border border-red-500/30 text-red-300 hover:text-red-200 px-4 py-1.5 rounded-lg transition">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Content --}}
    <main class="flex-1 flex items-center justify-center px-6 py-16">
        <div class="text-center">
            @if (session('success'))
                <div class="mb-6 inline-flex gap-3 items-center bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-5 py-3 text-sm text-emerald-300">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            <h1 class="text-4xl font-bold text-white mb-3">Welcome, {{ auth()->user()->name }}!</h1>
            <p class="text-slate-400 text-lg">You are signed in as <span class="text-indigo-400">{{ auth()->user()->email }}</span></p>
            <p class="mt-2 text-sm text-slate-500">Role: <span class="text-slate-300 font-medium">{{ auth()->user()->role->value }}</span></p>
        </div>
    </main>
</div>
@endsection

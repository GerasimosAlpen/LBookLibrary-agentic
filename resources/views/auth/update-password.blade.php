@extends('layouts.auth')

@section('title', 'Update Password')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600/30 border border-indigo-500/40 mb-4 shadow-lg shadow-indigo-900/50">
                <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Update password</h1>
            <p class="mt-1 text-sm text-slate-400">Keep your account secure</p>
        </div>

        {{-- Success / Error flash --}}
        @if (session('success'))
            <div class="mb-6 flex gap-3 items-center bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 text-sm text-emerald-300">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Card --}}
        <div class="bg-slate-800/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl shadow-black/40 p-8">

            @if ($errors->any())
                <div class="mb-6 flex gap-3 items-start bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-sm text-red-300">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <ul class="space-y-0.5 list-none">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="password-form" action="{{ route('auth.password') }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                {{-- Current Password --}}
                <div class="mb-5">
                    <label for="current_password" class="block text-sm font-medium text-slate-300 mb-1.5">Current Password</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="Your current password"
                        class="w-full px-4 py-2.5 bg-slate-900/60 border @error('current_password') border-red-500/60 @else border-slate-600/60 @enderror rounded-xl text-sm text-slate-100 placeholder-slate-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:border-indigo-500/60
                               transition duration-200"
                    />
                    @error('current_password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- New Password --}}
                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">New Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        minlength="8"
                        placeholder="Min. 8 characters"
                        class="w-full px-4 py-2.5 bg-slate-900/60 border @error('password') border-red-500/60 @else border-slate-600/60 @enderror rounded-xl text-sm text-slate-100 placeholder-slate-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:border-indigo-500/60
                               transition duration-200"
                    />
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm New Password --}}
                <div class="mb-7">
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-300 mb-1.5">Confirm New Password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        placeholder="Repeat new password"
                        class="w-full px-4 py-2.5 bg-slate-900/60 border border-slate-600/60 rounded-xl text-sm text-slate-100 placeholder-slate-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:border-indigo-500/60
                               transition duration-200"
                    />
                </div>

                {{-- Submit --}}
                <button
                    id="update-btn"
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700
                           text-white font-semibold text-sm rounded-xl px-4 py-3
                           transition duration-200 shadow-lg shadow-indigo-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                >
                    <span id="btn-text">Update Password</span>
                    <svg id="btn-spinner" class="hidden w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </button>
            </form>

            <div class="mt-6 flex justify-between text-sm text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-200 transition">← Back to Dashboard</a>
                <form action="{{ route('auth.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="hover:text-red-400 transition">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('password-form').addEventListener('submit', function () {
    const btn  = document.getElementById('update-btn');
    const text = document.getElementById('btn-text');
    const spin = document.getElementById('btn-spinner');
    btn.disabled = true;
    text.textContent = 'Updating…';
    spin.classList.remove('hidden');
});
</script>
@endsection

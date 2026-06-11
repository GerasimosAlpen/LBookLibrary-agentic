@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600/30 border border-indigo-500/40 mb-4 shadow-lg shadow-indigo-900/50">
                <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Welcome back</h1>
            <p class="mt-1 text-sm text-slate-400">Sign in to your BookLib account</p>
        </div>

        {{-- Success flash --}}
        @if (session('success'))
            <div class="mb-6 flex gap-3 items-center bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 text-sm text-emerald-300">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Card --}}
        <div class="bg-slate-800/60 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl shadow-black/40 p-8">

            {{-- Credential Error --}}
            @if ($errors->has('email'))
                <div id="error-banner" class="mb-6 flex gap-3 items-center bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-sm text-red-300">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    {{ $errors->first('email') }}
                </div>
            @endif

            <form id="login-form" action="{{ route('auth.login') }}" method="POST" novalidate>
                @csrf

                {{-- Email --}}
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">Email Address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        value="{{ old('email') }}"
                        required
                        placeholder="you@example.com"
                        class="w-full px-4 py-2.5 bg-slate-900/60 border @error('email') border-red-500/60 @else border-slate-600/60 @enderror rounded-xl text-sm text-slate-100 placeholder-slate-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:border-indigo-500/60
                               transition duration-200"
                    />
                </div>

                {{-- Password --}}
                <div class="mb-7">
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="Your password"
                            class="w-full px-4 py-2.5 pr-11 bg-slate-900/60 border border-slate-600/60 rounded-xl text-sm text-slate-100 placeholder-slate-500
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:border-indigo-500/60
                                   transition duration-200"
                        />
                        <button type="button" onclick="togglePwd()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <button
                    id="login-btn"
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700
                           text-white font-semibold text-sm rounded-xl px-4 py-3
                           transition duration-200 shadow-lg shadow-indigo-900/50 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                >
                    <span id="btn-text">Sign In</span>
                    <svg id="btn-spinner" class="hidden w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                Don't have an account?
                <a href="{{ route('auth.register') }}" class="text-indigo-400 hover:text-indigo-300 font-medium transition">Create one</a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const field = document.getElementById('password');
    field.type = field.type === 'password' ? 'text' : 'password';
}

document.getElementById('login-form').addEventListener('submit', function () {
    const btn  = document.getElementById('login-btn');
    const text = document.getElementById('btn-text');
    const spin = document.getElementById('btn-spinner');
    btn.disabled = true;
    text.textContent = 'Signing in…';
    spin.classList.remove('hidden');
});
</script>
@endsection

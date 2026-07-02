<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="BookLib — your digital book library catalog." />
    <title>@yield('title', 'Catalog') | BookLib</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 font-sans antialiased text-slate-100 min-h-screen">

    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 border-b border-slate-700/50 bg-slate-900/80 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Brand --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('books.index') }}" class="flex items-center gap-3 group">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center group-hover:bg-indigo-600/50 transition-all">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <span class="font-bold text-lg text-white tracking-tight">BookLib</span>
                    </a>
                </div>

                {{-- Nav Links --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('books.index') }}"
                       class="px-4 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('books.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        Books
                    </a>
                    <a href="{{ route('authors.index') }}"
                       class="px-4 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('authors.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        Authors
                    </a>
                    <a href="{{ route('categories.index') }}"
                       class="px-4 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('categories.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        Categories
                    </a>
                </div>

                {{-- User Actions --}}
                <div class="flex items-center gap-3">
                    @auth
                        <span class="hidden sm:inline text-xs text-slate-500 bg-slate-800/60 px-3 py-1 rounded-full border border-slate-700/50">
                            {{ auth()->user()->role->value }}
                        </span>
                        <a href="{{ route('auth.password') }}" class="text-sm text-slate-400 hover:text-slate-200 transition hidden sm:inline">
                            {{ auth()->user()->name }}
                        </a>
                        <form action="{{ route('auth.logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="text-sm bg-red-600/20 hover:bg-red-600/40 border border-red-500/30 text-red-300 hover:text-red-200 px-4 py-1.5 rounded-lg transition-all">
                                Sign Out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('auth.login') }}"
                           class="text-sm text-slate-400 hover:text-slate-200 transition px-3 py-1.5">Login</a>
                        <a href="{{ route('auth.register') }}"
                           class="text-sm bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-1.5 rounded-lg transition-all">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4" id="flash-success">
            <div class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-5 py-3 text-sm text-emerald-300">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-5 py-3 text-sm text-red-300">
                <p class="font-medium mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    {{-- Mobile Nav --}}
    <div class="md:hidden fixed bottom-0 inset-x-0 bg-slate-900/95 border-t border-slate-700/50 backdrop-blur-xl pb-safe">
        <div class="flex items-center justify-around py-2">
            <a href="{{ route('books.index') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 {{ request()->routeIs('books.*') ? 'text-indigo-400' : 'text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                <span class="text-xs">Books</span>
            </a>
            <a href="{{ route('authors.index') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 {{ request()->routeIs('authors.*') ? 'text-indigo-400' : 'text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-xs">Authors</span>
            </a>
            <a href="{{ route('categories.index') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 {{ request()->routeIs('categories.*') ? 'text-indigo-400' : 'text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <span class="text-xs">Categories</span>
            </a>
        </div>
    </div>

    <div class="h-16 md:hidden"></div>

</body>
</html>

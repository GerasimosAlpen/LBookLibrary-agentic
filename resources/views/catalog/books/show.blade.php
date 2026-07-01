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

    {{-- ─── Inventory Summary ─────────────────────────────────────────────────── --}}
    <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700/40">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Inventory</h2>
            </div>
            <a href="{{ route('books.copies.index', $book->id) }}"
               class="text-xs text-indigo-400 hover:text-indigo-300 transition font-medium"
               id="link-view-all-copies">
                View All Copies →
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 divide-x divide-slate-700/30">
            @php
                $copies       = $book->copies;
                $totalCopies  = $copies->count();
                $availCount   = $copies->where('status.value', 'AVAILABLE')->count();
                $borrowedCount= $copies->where('status.value', 'BORROWED')->count();
                $reservedCount= $copies->where('status.value', 'RESERVED')->count();
                $lostCount    = $copies->where('status.value', 'LOST')->count();
            @endphp

            <div class="p-5 flex flex-col items-center gap-1">
                <span class="text-2xl font-bold text-white">{{ $totalCopies }}</span>
                <span class="text-xs text-slate-500 uppercase tracking-wider">Total</span>
            </div>
            <div class="p-5 flex flex-col items-center gap-1">
                <span class="text-2xl font-bold text-emerald-400">{{ $availCount }}</span>
                <span class="text-xs text-emerald-400/60 uppercase tracking-wider">Available</span>
            </div>
            <div class="p-5 flex flex-col items-center gap-1">
                <span class="text-2xl font-bold text-amber-400">{{ $borrowedCount }}</span>
                <span class="text-xs text-amber-400/60 uppercase tracking-wider">Borrowed</span>
            </div>
            <div class="p-5 flex flex-col items-center gap-1">
                <span class="text-2xl font-bold text-blue-400">{{ $reservedCount }}</span>
                <span class="text-xs text-blue-400/60 uppercase tracking-wider">Reserved</span>
            </div>
            <div class="p-5 flex flex-col items-center gap-1">
                <span class="text-2xl font-bold text-red-400">{{ $lostCount }}</span>
                <span class="text-xs text-red-400/60 uppercase tracking-wider">Lost</span>
            </div>
        </div>

        {{-- Availability Indicator --}}
        <div class="px-6 py-3 border-t border-slate-700/30">
            @if($totalCopies === 0)
                <span class="inline-flex items-center gap-2 text-xs text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                    No copies registered
                </span>
            @elseif($availCount > 0)
                <span class="inline-flex items-center gap-2 text-xs text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Available — {{ $availCount }} {{ Str::plural('copy', $availCount) }} ready to borrow
                </span>
            @else
                <span class="inline-flex items-center gap-2 text-xs text-red-400">
                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                    Out of Stock
                </span>
            @endif
        </div>
    </div>

    {{-- ─── Borrow Interface ──────────────────────────────────────────────────── --}}
    @auth
        <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-2 px-6 py-4 border-b border-slate-700/40">
                <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Borrow a Copy</h2>
            </div>

            @php
                $availableCopies = $book->copies->where('status.value', 'AVAILABLE');
            @endphp

            @if($availableCopies->isEmpty())
                <div class="px-6 py-8 text-center">
                    <p class="text-slate-400 text-sm">No copies are currently available for borrowing.</p>
                    <p class="text-slate-500 text-xs mt-1">Check back later or ask a librarian.</p>
                </div>
            @else
                <div class="divide-y divide-slate-700/30">
                    @foreach($availableCopies as $copy)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-slate-200 font-mono">{{ $copy->barcode }}</p>
                                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-400 mt-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    Available
                                </span>
                            </div>
                            <form action="{{ route('transactions.borrow') }}" method="POST">
                                @csrf
                                <input type="hidden" name="copy_id" value="{{ $copy->id }}">
                                <button id="btn-borrow-copy-{{ $copy->id }}"
                                        type="submit"
                                        onclick="return confirm('Borrow {{ addslashes($copy->barcode) }}? Due date will be 14 days from today.')"
                                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-md shadow-indigo-600/20">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Borrow
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endauth

    {{-- ─── Reserve Interface ──────────────────────────────────────────────────── --}}
    @auth
        @php
            $availCount = $book->copies->where('status.value', 'AVAILABLE')->count();
            $existingReservation = $book->reservations
                ->where('user_id', auth()->id())
                ->whereIn('status.value', ['PENDING'])
                ->first();
        @endphp

        <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-2 px-6 py-4 border-b border-slate-700/40">
                <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Reserve This Book</h2>
            </div>

            <div class="px-6 py-5">
                @if($availCount > 0)
                    {{-- Cannot reserve: copies available --}}
                    <div class="flex items-center gap-3 text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3 mb-4">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $availCount }} {{ Str::plural('copy', $availCount) }} available — please borrow directly instead.
                    </div>
                    <button disabled
                            class="inline-flex items-center gap-2 bg-slate-700/40 border border-slate-600/30 text-slate-500 px-5 py-2.5 rounded-xl text-sm font-medium cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Reserve (Not Needed)
                    </button>
                @elseif($existingReservation)
                    {{-- Already reserved --}}
                    <div class="flex items-center gap-3 text-sm text-violet-300 bg-violet-500/10 border border-violet-500/20 rounded-xl px-4 py-3 mb-4">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        You are already in the queue at position <strong>#{{ $existingReservation->queue_position }}</strong>.
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('reservations.show', $existingReservation->id) }}"
                           class="inline-flex items-center gap-2 bg-violet-600/20 hover:bg-violet-600/30 border border-violet-500/30 text-violet-300 px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
                            View My Reservation
                        </a>
                        <form action="{{ route('reservations.cancel', $existingReservation->id) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <button id="btn-cancel-from-book-{{ $existingReservation->id }}"
                                    type="submit"
                                    onclick="return confirm('Cancel your reservation for this book?')"
                                    class="inline-flex items-center gap-2 bg-rose-600/20 hover:bg-rose-600/30 border border-rose-500/30 text-rose-300 px-4 py-2.5 rounded-xl text-sm font-medium transition-all">
                                Cancel Reservation
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Eligible to reserve --}}
                    <p class="text-slate-400 text-sm mb-4">All copies are currently borrowed. Reserve to hold your place in the queue.</p>
                    <form action="{{ route('reservations.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ $book->id }}">
                        <button id="btn-reserve-book-{{ $book->id }}"
                                type="submit"
                                onclick="return confirm('Reserve {{ addslashes($book->title) }}? You will be placed in the queue.')"
                                class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-all shadow-lg shadow-violet-600/20">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Reserve This Book
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Admin: view full queue --}}
        @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
            <div class="bg-slate-800/30 border border-slate-700/30 rounded-xl px-5 py-3 flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    <span class="text-slate-300 font-medium">{{ $book->reservations->whereIn('status.value', ['PENDING'])->count() }}</span> active reservation(s) in queue
                </p>
                <a href="{{ route('books.reservations', $book->id) }}"
                   class="text-xs text-violet-400 hover:text-violet-300 transition-colors font-medium">
                    View Reservation Queue →
                </a>
            </div>
        @endif
    @endauth

    {{-- Admin Actions --}}
    @auth
        @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('books.edit', $book->id) }}"
                   class="inline-flex items-center gap-2 bg-amber-600/20 hover:bg-amber-600/30 border border-amber-500/30 text-amber-300 hover:text-amber-200 text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Book
                </a>
                <a href="{{ route('books.copies.index', $book->id) }}"
                   class="inline-flex items-center gap-2 bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/30 text-indigo-300 hover:text-indigo-200 text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Manage Copies
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

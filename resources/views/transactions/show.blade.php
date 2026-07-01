@extends('layouts.app')

@section('title', 'Transaction #' . $transaction->id)

@section('content')
<div class="space-y-8 max-w-3xl mx-auto">

    {{-- Back Link --}}
    <div>
        <a href="{{ route('transactions.index') }}"
           class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Transactions
        </a>
    </div>

    {{-- Transaction Card --}}
    <div class="bg-slate-900/60 border border-slate-700/50 rounded-2xl overflow-hidden shadow-2xl">

        {{-- Header --}}
        <div class="px-8 py-6 border-b border-slate-700/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">Transaction #{{ $transaction->id }}</h1>
                <p class="text-slate-400 text-sm mt-1">Created {{ $transaction->created_at->diffForHumans() }}</p>
            </div>

            {{-- Status Badge --}}
            @php $status = $transaction->status->value; @endphp
            @if($status === 'ACTIVE')
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/25">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Active
                </span>
            @elseif($status === 'OVERDUE')
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-rose-500/15 text-rose-300 border border-rose-500/25">
                    <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                    Overdue
                </span>
            @else
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-slate-700/50 text-slate-300 border border-slate-600/40">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Returned
                </span>
            @endif
        </div>

        {{-- Book Info --}}
        <div class="px-8 py-6 border-b border-slate-700/40">
            <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Book Details</h2>
            <div class="flex items-start gap-5">
                <div class="w-14 h-14 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shrink-0">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
                <div>
                    <p class="text-lg font-semibold text-white">{{ $transaction->copy->book->title ?? '—' }}</p>
                    <p class="text-slate-400 text-sm">by {{ $transaction->copy->book->author->name ?? '—' }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-mono">{{ $transaction->copy->barcode ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Dates & Fine --}}
        <div class="px-8 py-6 border-b border-slate-700/40 grid grid-cols-2 sm:grid-cols-4 gap-6">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Borrow Date</p>
                <p class="text-sm font-medium text-slate-200">{{ $transaction->borrow_date?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Due Date</p>
                @php $isLate = !$transaction->return_date && $transaction->due_date && $transaction->due_date < now(); @endphp
                <p class="text-sm font-medium {{ $isLate ? 'text-rose-400' : 'text-slate-200' }}">
                    {{ $transaction->due_date?->format('d M Y') ?? '—' }}
                    @if($isLate)
                        <span class="text-xs text-rose-500">(overdue)</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Return Date</p>
                <p class="text-sm font-medium text-slate-200">{{ $transaction->return_date?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Fine Amount</p>
                @if($transaction->fine_amount > 0)
                    <p class="text-sm font-semibold text-rose-400">{{ number_format($transaction->fine_amount) }}</p>
                @else
                    <p class="text-sm font-medium text-emerald-400">None</p>
                @endif
            </div>
        </div>

        {{-- Borrower (Admin / Librarian only) --}}
        @if(auth()->user()->role->value === 'ADMIN' || auth()->user()->role->value === 'LIBRARIAN')
            <div class="px-8 py-6 border-b border-slate-700/40">
                <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Borrower</h2>
                <p class="text-sm font-medium text-slate-200">{{ $transaction->user->name ?? '—' }}</p>
                <p class="text-xs text-slate-500">{{ $transaction->user->email ?? '—' }}</p>
            </div>
        @endif

        {{-- Actions --}}
        @if($status !== 'RETURNED')
            <div class="px-8 py-6 bg-slate-800/30">
                <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Actions</h2>
                <div class="flex flex-wrap items-center gap-3">

                    {{-- Return --}}
                    <form action="{{ route('transactions.return', $transaction->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button id="btn-return-{{ $transaction->id }}"
                                type="submit"
                                onclick="return confirm('Return this book now?')"
                                class="inline-flex items-center gap-2 bg-emerald-600/20 hover:bg-emerald-600/40 border border-emerald-500/30 text-emerald-300 hover:text-emerald-200 px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                            </svg>
                            Return Book
                        </button>
                    </form>

                    {{-- Extend (only ACTIVE) --}}
                    @if($status === 'ACTIVE')
                        <form action="{{ route('transactions.extend', $transaction->id) }}" method="POST" class="flex items-center gap-2">
                            @csrf @method('PATCH')
                            <input type="number"
                                   name="days"
                                   id="extend-days-{{ $transaction->id }}"
                                   value="7"
                                   min="1"
                                   max="30"
                                   class="w-20 bg-slate-800/60 border border-slate-600/50 text-slate-200 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/30">
                            <button id="btn-extend-{{ $transaction->id }}"
                                    type="submit"
                                    class="inline-flex items-center gap-2 bg-amber-600/20 hover:bg-amber-600/40 border border-amber-500/30 text-amber-300 hover:text-amber-200 px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Extend (days)
                            </button>
                        </form>
                    @else
                        <div class="inline-flex items-center gap-2 bg-slate-700/30 border border-slate-600/30 text-slate-500 px-5 py-2.5 rounded-xl text-sm cursor-not-allowed"
                             title="Overdue transactions cannot be extended">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Extension Unavailable
                        </div>
                    @endif
                </div>
                @if($status === 'OVERDUE')
                    <p class="text-xs text-rose-400 mt-3">
                        ⚠ This book is overdue. Extensions are only allowed for active transactions. Please return the book to clear the fine.
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

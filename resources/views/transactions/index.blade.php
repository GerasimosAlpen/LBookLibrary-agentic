@extends('layouts.app')

@section('title', 'My Transactions')

@section('content')
<div class="space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Transactions</h1>
            <p class="text-slate-400 text-sm mt-1">
                @if(auth()->user()->role->value === 'ADMIN' || auth()->user()->role->value === 'LIBRARIAN')
                    All borrowing transactions in the system.
                @else
                    Your borrowing history and active loans.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if(auth()->user()->role->value === 'ADMIN' || auth()->user()->role->value === 'LIBRARIAN')
                <a href="{{ route('transactions.overdue') }}"
                   class="inline-flex items-center gap-2 bg-rose-600/20 hover:bg-rose-600/40 border border-rose-500/30 text-rose-300 hover:text-rose-200 px-4 py-2 rounded-xl text-sm font-medium transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Overdue
                </a>
            @endif
            <a href="{{ route('books.index') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-lg shadow-indigo-600/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .513v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
                Browse Books
            </a>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $active   = $transactions->where('status.value', 'ACTIVE')->count();
        $overdue  = $transactions->where('status.value', 'OVERDUE')->count();
        $returned = $transactions->where('status.value', 'RETURNED')->count();
        $totalFines = $transactions->sum('fine_amount');
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900/60 border border-slate-700/50 rounded-2xl p-5 text-center">
            <p class="text-2xl font-bold text-white">{{ $transactions->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Total</p>
        </div>
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-5 text-center">
            <p class="text-2xl font-bold text-emerald-300">{{ $active }}</p>
            <p class="text-xs text-slate-400 mt-1">Active</p>
        </div>
        <div class="bg-rose-500/10 border border-rose-500/30 rounded-2xl p-5 text-center">
            <p class="text-2xl font-bold text-rose-300">{{ $overdue }}</p>
            <p class="text-xs text-slate-400 mt-1">Overdue</p>
        </div>
        <div class="bg-slate-700/30 border border-slate-600/30 rounded-2xl p-5 text-center">
            <p class="text-2xl font-bold text-slate-300">{{ $returned }}</p>
            <p class="text-xs text-slate-400 mt-1">Returned</p>
        </div>
    </div>

    {{-- Transactions Table --}}
    @if ($transactions->isEmpty())
        <div class="text-center py-20 bg-slate-900/40 border border-slate-700/40 rounded-2xl">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-800/60 border border-slate-700/50 flex items-center justify-center text-slate-500 mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                </svg>
            </div>
            <h3 class="text-slate-300 font-semibold text-lg">No transactions yet</h3>
            <p class="text-slate-500 text-sm mt-1">Browse the catalog and borrow a book to get started.</p>
            <a href="{{ route('books.index') }}"
               class="inline-flex items-center gap-2 mt-6 bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
                Browse Books
            </a>
        </div>
    @else
        <div class="bg-slate-900/60 border border-slate-700/50 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700/50">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Book / Copy</th>
                            @if(auth()->user()->role->value !== 'MEMBER')
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Borrower</th>
                            @endif
                            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Borrow Date</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Due Date</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Return Date</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Fine</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                        @foreach($transactions as $transaction)
                            <tr class="hover:bg-slate-800/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-white group-hover:text-indigo-300 transition-colors">
                                            {{ $transaction->copy->book->title ?? '—' }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $transaction->copy->barcode ?? '—' }}</p>
                                    </div>
                                </td>
                                @if(auth()->user()->role->value !== 'MEMBER')
                                    <td class="px-6 py-4 text-slate-300">
                                        {{ $transaction->user->name ?? '—' }}
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-slate-400 text-xs">
                                    {{ $transaction->borrow_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if($transaction->due_date)
                                        @php $isLate = !$transaction->return_date && $transaction->due_date < now(); @endphp
                                        <span class="{{ $isLate ? 'text-rose-400 font-semibold' : 'text-slate-400' }}">
                                            {{ $transaction->due_date->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-xs">
                                    {{ $transaction->return_date?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if($transaction->fine_amount > 0)
                                        <span class="text-rose-400 font-semibold">
                                            {{ number_format($transaction->fine_amount) }}
                                        </span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php $status = $transaction->status->value; @endphp
                                    @if($status === 'ACTIVE')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/25">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            Active
                                        </span>
                                    @elseif($status === 'OVERDUE')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-500/15 text-rose-300 border border-rose-500/25">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                                            Overdue
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-700/50 text-slate-400 border border-slate-600/40">
                                            Returned
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('transactions.show', $transaction->id) }}"
                                           class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors px-2 py-1 rounded hover:bg-indigo-500/10">
                                            View
                                        </a>
                                        @if($status === 'ACTIVE')
                                            <form action="{{ route('transactions.return', $transaction->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                        onclick="return confirm('Return this book?')"
                                                        class="text-xs text-emerald-400 hover:text-emerald-300 transition-colors px-2 py-1 rounded hover:bg-emerald-500/10">
                                                    Return
                                                </button>
                                            </form>
                                            <a href="{{ route('transactions.show', $transaction->id) }}"
                                               class="text-xs text-amber-400 hover:text-amber-300 transition-colors px-2 py-1 rounded hover:bg-amber-500/10">
                                                Extend
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

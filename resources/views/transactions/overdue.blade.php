@extends('layouts.app')

@section('title', 'Overdue Transactions')

@section('content')
<div class="space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 border border-rose-500/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white tracking-tight">Overdue Transactions</h1>
            </div>
            <p class="text-slate-400 text-sm">Books not returned by their due date.</p>
        </div>
        <a href="{{ route('transactions.index') }}"
           class="inline-flex items-center gap-2 bg-slate-800/60 hover:bg-slate-700/60 border border-slate-600/50 text-slate-300 hover:text-white px-4 py-2 rounded-xl text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            All Transactions
        </a>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-rose-500/10 border border-rose-500/30 rounded-2xl p-5 text-center">
            <p class="text-3xl font-bold text-rose-300">{{ $transactions->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Overdue Records</p>
        </div>
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-5 text-center">
            <p class="text-3xl font-bold text-amber-300">{{ number_format($transactions->sum('fine_amount')) }}</p>
            <p class="text-xs text-slate-400 mt-1">Total Fines</p>
        </div>
        <div class="bg-slate-800/40 border border-slate-600/40 rounded-2xl p-5 text-center">
            @php
                $mostOverdue = $transactions->sortBy('due_date')->first();
            @endphp
            <p class="text-3xl font-bold text-slate-200">
                {{ $mostOverdue ? $mostOverdue->due_date->diffForHumans(['parts' => 1, 'short' => true]) : '—' }}
            </p>
            <p class="text-xs text-slate-400 mt-1">Longest Overdue</p>
        </div>
    </div>

    {{-- Table --}}
    @if($transactions->isEmpty())
        <div class="text-center py-20 bg-slate-900/40 border border-slate-700/40 rounded-2xl">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-emerald-300 font-semibold text-lg">No overdue transactions!</h3>
            <p class="text-slate-500 text-sm mt-1">All borrowed books are within their due dates.</p>
        </div>
    @else
        <div class="bg-slate-900/60 border border-rose-500/20 rounded-2xl overflow-hidden shadow-2xl shadow-rose-500/5">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-rose-500/20 bg-rose-500/5">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-rose-300/70 uppercase tracking-wider">Book / Copy</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-rose-300/70 uppercase tracking-wider">Borrower</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-rose-300/70 uppercase tracking-wider">Due Date</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-rose-300/70 uppercase tracking-wider">Days Overdue</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-rose-300/70 uppercase tracking-wider">Fine (est.)</th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-rose-300/70 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-500/10">
                        @foreach($transactions as $transaction)
                            @php
                                $daysOverdue = (int) ceil($transaction->due_date->diffInHours(now()) / 24);
                                $estFine     = $daysOverdue * 1000;
                            @endphp
                            <tr class="hover:bg-rose-500/5 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-white">{{ $transaction->copy->book->title ?? '—' }}</p>
                                    <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $transaction->copy->barcode ?? '—' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-300">{{ $transaction->user->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $transaction->user->email ?? '—' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-rose-400 font-medium text-xs">
                                        {{ $transaction->due_date->format('d M Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                        {{ $daysOverdue }} day{{ $daysOverdue !== 1 ? 's' : '' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-rose-400 font-semibold text-xs">
                                        {{ number_format($estFine) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('transactions.show', $transaction->id) }}"
                                           class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors px-2 py-1 rounded hover:bg-indigo-500/10">
                                            View
                                        </a>
                                        <form action="{{ route('transactions.return', $transaction->id) }}" method="POST" class="inline">
                                            @csrf @method('PATCH')
                                            <button id="btn-overdue-return-{{ $transaction->id }}"
                                                    type="submit"
                                                    onclick="return confirm('Process return for this overdue book?')"
                                                    class="text-xs text-emerald-400 hover:text-emerald-300 transition-colors px-2 py-1 rounded hover:bg-emerald-500/10">
                                                Return
                                            </button>
                                        </form>
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

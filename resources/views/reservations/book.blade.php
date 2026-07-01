@extends('layouts.app')

@section('title', 'Reservations for ' . $book->title)

@section('content')
<div class="space-y-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('books.index') }}" class="hover:text-slate-300 transition">Books</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ route('books.show', $book->id) }}" class="hover:text-slate-300 transition truncate max-w-xs">{{ $book->title }}</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-400">Reservations</span>
    </nav>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Reservation Queue</h1>
            <p class="text-indigo-400 mt-1">{{ $book->title }}</p>
            <p class="text-slate-500 text-sm">by {{ $book->author->name ?? '—' }}</p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <div class="bg-violet-500/10 border border-violet-500/30 rounded-xl px-4 py-2 text-center">
                <p class="text-2xl font-bold text-violet-300">{{ $queue->count() }}</p>
                <p class="text-xs text-slate-400">In Queue</p>
            </div>
        </div>
    </div>

    {{-- Active Queue --}}
    <div class="bg-slate-900/60 border border-violet-500/20 rounded-2xl overflow-hidden">
        <div class="flex items-center gap-2 px-6 py-4 border-b border-violet-500/20 bg-violet-500/5">
            <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <h2 class="text-sm font-semibold text-violet-300 uppercase tracking-wider">Active Queue (PENDING)</h2>
        </div>

        @if($queue->isEmpty())
            <div class="px-6 py-10 text-center">
                <p class="text-slate-400 text-sm">No active reservations in the queue.</p>
            </div>
        @else
            <div class="divide-y divide-violet-500/10">
                @foreach($queue as $reservation)
                    <div class="flex items-center px-6 py-4 gap-4">
                        {{-- Position badge --}}
                        <div class="w-10 h-10 rounded-full bg-violet-500/20 border border-violet-500/30 flex items-center justify-center shrink-0">
                            <span class="text-violet-300 font-bold text-sm">{{ $reservation->queue_position }}</span>
                        </div>
                        {{-- User info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-slate-200 font-medium truncate">{{ $reservation->user->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500">{{ $reservation->user->email ?? '' }} · Reserved {{ $reservation->reserved_at->diffForHumans() }}</p>
                        </div>
                        {{-- Status + Action --}}
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                PENDING
                            </span>
                            <a href="{{ route('reservations.show', $reservation->id) }}"
                               class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors px-2 py-1 rounded hover:bg-indigo-500/10">
                                View
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Full History --}}
    @if($reservations->isNotEmpty())
        <div class="bg-slate-900/40 border border-slate-700/40 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-2 px-6 py-4 border-b border-slate-700/40">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Full Reservation History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700/30 bg-slate-800/30">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Member</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Queue #</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reserved At</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/20">
                        @foreach($reservations as $reservation)
                            @php
                                $sc = match($reservation->status->value) {
                                    'PENDING'   => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                    'FULFILLED' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                    'CANCELLED' => 'bg-slate-700/40 text-slate-400 border-slate-600/30',
                                    default     => 'bg-slate-700/40 text-slate-400 border-slate-600/30',
                                };
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-3">
                                    <p class="text-slate-300">{{ $reservation->user->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $reservation->user->email ?? '' }}</p>
                                </td>
                                <td class="px-6 py-3 text-slate-400 text-xs font-mono">
                                    {{ $reservation->status->value === 'PENDING' ? '#' . $reservation->queue_position : '—' }}
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-400">
                                    {{ $reservation->reserved_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $sc }}">
                                        {{ $reservation->status->value }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('reservations.show', $reservation->id) }}"
                                       class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors px-2 py-1 rounded hover:bg-indigo-500/10">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div>
        <a href="{{ route('books.show', $book->id) }}" class="text-sm text-slate-500 hover:text-slate-300 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Book
        </a>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'My Reservations')

@section('content')
<div class="space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-8 h-8 rounded-lg bg-violet-500/20 border border-violet-500/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white tracking-tight">
                    @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                        All Reservations
                    @else
                        My Reservations
                    @endif
                </h1>
            </div>
            <p class="text-slate-400 text-sm">Track book reservations and queue positions.</p>
        </div>
        <a href="{{ route('books.index') }}"
           class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-all shadow-lg shadow-violet-600/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Browse Books
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 rounded-xl px-5 py-4 text-sm flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-500/10 border border-rose-500/30 text-rose-300 rounded-xl px-5 py-4 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stats Summary --}}
    @php
        $pending   = $reservations->where('status.value', 'PENDING');
        $fulfilled = $reservations->where('status.value', 'FULFILLED');
        $cancelled = $reservations->where('status.value', 'CANCELLED');
    @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-5 text-center">
            <p class="text-2xl font-bold text-amber-300">{{ $pending->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Pending</p>
        </div>
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-5 text-center">
            <p class="text-2xl font-bold text-emerald-300">{{ $fulfilled->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Fulfilled</p>
        </div>
        <div class="bg-slate-800/40 border border-slate-600/40 rounded-2xl p-5 text-center">
            <p class="text-2xl font-bold text-slate-400">{{ $cancelled->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Cancelled</p>
        </div>
    </div>

    {{-- Table --}}
    @if($reservations->isEmpty())
        <div class="text-center py-20 bg-slate-900/40 border border-slate-700/40 rounded-2xl">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-violet-500/10 border border-violet-500/30 flex items-center justify-center text-violet-400 mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-slate-300 font-semibold text-lg">No reservations yet</h3>
            <p class="text-slate-500 text-sm mt-1">Browse the catalog and reserve a book when no copies are available.</p>
            <a href="{{ route('books.index') }}"
               class="inline-flex items-center gap-2 mt-5 bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
                Browse Catalog
            </a>
        </div>
    @else
        <div class="bg-slate-900/60 border border-violet-500/20 rounded-2xl overflow-hidden shadow-2xl shadow-violet-500/5">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-violet-500/20 bg-violet-500/5">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-violet-300/70 uppercase tracking-wider">Book</th>
                            @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                                <th class="text-left px-6 py-4 text-xs font-semibold text-violet-300/70 uppercase tracking-wider">Member</th>
                            @endif
                            <th class="text-left px-6 py-4 text-xs font-semibold text-violet-300/70 uppercase tracking-wider">Queue #</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-violet-300/70 uppercase tracking-wider">Reserved</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-violet-300/70 uppercase tracking-wider">Status</th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-violet-300/70 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-violet-500/10">
                        @foreach($reservations as $reservation)
                            <tr class="hover:bg-violet-500/5 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="{{ route('books.show', $reservation->book->id) }}"
                                       class="font-medium text-white hover:text-violet-300 transition-colors">
                                        {{ $reservation->book->title ?? '—' }}
                                    </a>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $reservation->book->author->name ?? '' }}</p>
                                </td>
                                @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                                    <td class="px-6 py-4">
                                        <p class="text-slate-300">{{ $reservation->user->name ?? '—' }}</p>
                                        <p class="text-xs text-slate-500">{{ $reservation->user->email ?? '' }}</p>
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    @if($reservation->status->value === 'PENDING')
                                        <span class="inline-flex items-center gap-1.5 text-sm font-bold text-violet-300">
                                            <span class="w-2 h-2 rounded-full bg-violet-400 animate-pulse"></span>
                                            #{{ $reservation->queue_position }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 text-sm">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400">
                                    {{ $reservation->reserved_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClasses = match($reservation->status->value) {
                                            'PENDING'   => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                            'FULFILLED' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                            'CANCELLED' => 'bg-slate-700/40 text-slate-400 border-slate-600/30',
                                            default     => 'bg-slate-700/40 text-slate-400 border-slate-600/30',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusClasses }}">
                                        {{ $reservation->status->value }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('reservations.show', $reservation->id) }}"
                                           class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors px-2 py-1 rounded hover:bg-indigo-500/10">
                                            View
                                        </a>
                                        @if($reservation->status->value === 'PENDING')
                                            <form action="{{ route('reservations.cancel', $reservation->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button id="btn-cancel-reservation-{{ $reservation->id }}"
                                                        type="submit"
                                                        onclick="return confirm('Cancel this reservation?')"
                                                        class="text-xs text-rose-400 hover:text-rose-300 transition-colors px-2 py-1 rounded hover:bg-rose-500/10">
                                                    Cancel
                                                </button>
                                            </form>
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

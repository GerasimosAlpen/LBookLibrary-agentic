@extends('layouts.app')

@section('title', 'Reservation #' . $reservation->id)

@section('content')
<div class="max-w-2xl mx-auto space-y-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('reservations.index') }}" class="hover:text-slate-300 transition">Reservations</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-400">Reservation #{{ $reservation->id }}</span>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 rounded-xl px-5 py-4 text-sm flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Status Badge --}}
    @php
        $statusConfig = match($reservation->status->value) {
            'PENDING'   => ['bg-amber-500/15 border-amber-500/30 text-amber-300',  'w-3 h-3 rounded-full bg-amber-400 animate-pulse'],
            'FULFILLED' => ['bg-emerald-500/15 border-emerald-500/30 text-emerald-300', 'w-3 h-3 rounded-full bg-emerald-400'],
            'CANCELLED' => ['bg-slate-700/40 border-slate-600/30 text-slate-400',  'w-3 h-3 rounded-full bg-slate-500'],
            default     => ['bg-slate-700/40 border-slate-600/30 text-slate-400',  ''],
        };
    @endphp

    {{-- Main Card --}}
    <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-700/40">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-500/20 border border-violet-500/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Reservation</p>
                    <p class="text-white font-semibold">#{{ $reservation->id }}</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border {{ $statusConfig[0] }}">
                <span class="{{ $statusConfig[1] }}"></span>
                {{ $reservation->status->value }}
            </span>
        </div>

        {{-- Details Grid --}}
        <div class="divide-y divide-slate-700/30">
            {{-- Book --}}
            <div class="flex items-start justify-between px-6 py-4">
                <p class="text-xs text-slate-500 uppercase tracking-wider w-28 shrink-0 pt-0.5">Book</p>
                <div class="flex-1 text-right">
                    <a href="{{ route('books.show', $reservation->book->id) }}"
                       class="font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                        {{ $reservation->book->title ?? '—' }}
                    </a>
                    <p class="text-xs text-slate-500 mt-0.5">by {{ $reservation->book->author->name ?? '—' }}</p>
                </div>
            </div>

            {{-- Member (admin/librarian only) --}}
            @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                <div class="flex items-center justify-between px-6 py-4">
                    <p class="text-xs text-slate-500 uppercase tracking-wider w-28 shrink-0">Member</p>
                    <div class="text-right">
                        <p class="text-slate-200 font-medium">{{ $reservation->user->name ?? '—' }}</p>
                        <p class="text-xs text-slate-500">{{ $reservation->user->email ?? '' }}</p>
                    </div>
                </div>
            @endif

            {{-- Queue Position --}}
            <div class="flex items-center justify-between px-6 py-4">
                <p class="text-xs text-slate-500 uppercase tracking-wider w-28 shrink-0">Queue Position</p>
                @if($reservation->status->value === 'PENDING')
                    <span class="inline-flex items-center gap-2 text-lg font-bold text-violet-300">
                        <span class="w-2.5 h-2.5 rounded-full bg-violet-400 animate-pulse"></span>
                        #{{ $reservation->queue_position }}
                    </span>
                @else
                    <span class="text-slate-500 text-sm">N/A</span>
                @endif
            </div>

            {{-- Reserved At --}}
            <div class="flex items-center justify-between px-6 py-4">
                <p class="text-xs text-slate-500 uppercase tracking-wider w-28 shrink-0">Reserved At</p>
                <p class="text-slate-300 text-sm">{{ $reservation->reserved_at->format('d M Y, H:i') }}</p>
            </div>

            {{-- Status --}}
            <div class="flex items-center justify-between px-6 py-4">
                <p class="text-xs text-slate-500 uppercase tracking-wider w-28 shrink-0">Status</p>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusConfig[0] }}">
                    <span class="{{ $statusConfig[1] }}"></span>
                    {{ $reservation->status->value }}
                </span>
            </div>
        </div>

        {{-- Actions --}}
        @if($reservation->status->value === 'PENDING')
            <div class="px-6 py-5 border-t border-slate-700/40 flex items-center justify-end gap-3">
                <form action="{{ route('reservations.cancel', $reservation->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <button id="btn-cancel-reservation-detail-{{ $reservation->id }}"
                            type="submit"
                            onclick="return confirm('Cancel this reservation? This action cannot be undone.')"
                            class="inline-flex items-center gap-2 bg-rose-600/20 hover:bg-rose-600/30 border border-rose-500/30 text-rose-300 hover:text-rose-200 text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel Reservation
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- Queue Context (only for PENDING) --}}
    @if($reservation->status->value === 'PENDING')
        <div class="bg-violet-500/5 border border-violet-500/20 rounded-2xl p-5">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-violet-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-violet-300 font-medium text-sm">Queue Position #{{ $reservation->queue_position }}</p>
                    <p class="text-slate-400 text-xs mt-1">
                        You will be notified when a copy of this book becomes available.
                        Reservations are fulfilled in first-come, first-served order.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Admin: view book reservation queue --}}
    @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
        <a href="{{ route('books.reservations', $reservation->book->id) }}"
           class="block text-center text-sm text-violet-400 hover:text-violet-300 transition-colors py-2">
            View full reservation queue for this book →
        </a>
    @endif

    <div>
        <a href="{{ route('reservations.index') }}" class="text-sm text-slate-500 hover:text-slate-300 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Reservations
        </a>
    </div>
</div>
@endsection

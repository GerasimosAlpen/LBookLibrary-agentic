@extends('layouts.app')

@section('title', 'Inventory — ' . $bookModel->title)

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('books.index') }}" class="hover:text-slate-300 transition">Books</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('books.show', $bookModel->id) }}" class="hover:text-slate-300 transition truncate max-w-xs">{{ $bookModel->title }}</a>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-400">Inventory</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Physical Copies</h1>
            <p class="text-slate-400 text-sm mt-1">{{ $bookModel->title }}</p>
        </div>
        @auth
            @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                <button
                    onclick="document.getElementById('add-copy-modal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-600/20"
                    id="btn-add-copy">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Copy
                </button>
            @endif
        @endauth
    </div>

    {{-- Availability Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        {{-- Total --}}
        <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl p-5 flex flex-col items-center gap-1">
            <span class="text-3xl font-bold text-white">{{ $availability['total'] }}</span>
            <span class="text-xs text-slate-400 uppercase tracking-wider font-medium">Total</span>
        </div>
        {{-- Available --}}
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-5 flex flex-col items-center gap-1">
            <span class="text-3xl font-bold text-emerald-400">{{ $availability['available'] }}</span>
            <span class="text-xs text-emerald-400/70 uppercase tracking-wider font-medium">Available</span>
        </div>
        {{-- Borrowed --}}
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-5 flex flex-col items-center gap-1">
            <span class="text-3xl font-bold text-amber-400">{{ $availability['borrowed'] }}</span>
            <span class="text-xs text-amber-400/70 uppercase tracking-wider font-medium">Borrowed</span>
        </div>
        {{-- Reserved --}}
        <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-5 flex flex-col items-center gap-1">
            <span class="text-3xl font-bold text-blue-400">{{ $availability['reserved'] }}</span>
            <span class="text-xs text-blue-400/70 uppercase tracking-wider font-medium">Reserved</span>
        </div>
        {{-- Lost --}}
        <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-5 flex flex-col items-center gap-1">
            <span class="text-3xl font-bold text-red-400">{{ $availability['lost'] }}</span>
            <span class="text-xs text-red-400/70 uppercase tracking-wider font-medium">Lost</span>
        </div>
    </div>

    {{-- Availability Banner --}}
    @if($availability['total'] === 0)
        <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl p-6 flex items-center gap-3 text-slate-400">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm">No physical copies registered for this book yet.</span>
        </div>
    @elseif($availability['available'] === 0)
        <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-red-400 shrink-0 animate-pulse"></span>
            <span class="text-sm font-medium text-red-300">Out of Stock — no copies currently available</span>
        </div>
    @else
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0 animate-pulse"></span>
            <span class="text-sm font-medium text-emerald-300">{{ $availability['available'] }} {{ Str::plural('copy', $availability['available']) }} available for borrowing</span>
        </div>
    @endif

    {{-- Copy List --}}
    @if($copies->isNotEmpty())
        <div class="bg-slate-800/50 border border-slate-700/40 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700/40">
                <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Copy List</h2>
            </div>

            <div class="divide-y divide-slate-700/30">
                @foreach($copies as $copy)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 py-4" id="copy-{{ $copy->id }}">
                        {{-- Copy Info --}}
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-900/60 border border-slate-700/40 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white font-mono">COPY-{{ str_pad($copy->id, 5, '0', STR_PAD_LEFT) }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">Added {{ $copy->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>

                        {{-- Status Badge + Actions --}}
                        <div class="flex items-center gap-3 flex-wrap">
                            {{-- Status Badge --}}
                            @php
                                $status = $copy->status->value;
                                $badgeClasses = match($status) {
                                    'AVAILABLE' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20',
                                    'BORROWED'  => 'bg-amber-500/15 text-amber-400 border-amber-500/20',
                                    'RESERVED'  => 'bg-blue-500/15 text-blue-400 border-blue-500/20',
                                    'LOST'      => 'bg-red-500/15 text-red-400 border-red-500/20',
                                    default     => 'bg-slate-700/50 text-slate-400 border-slate-600/40',
                                };
                            @endphp
                            <span class="inline-flex items-center text-xs font-medium px-3 py-1 rounded-full border {{ $badgeClasses }}">
                                {{ $status }}
                            </span>

                            @auth
                                @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
                                    {{-- Edit Button --}}
                                    <button
                                        onclick="openEditModal({{ $copy->id }}, '{{ $status }}')"
                                        class="text-xs bg-slate-700/50 hover:bg-slate-700 border border-slate-600/40 text-slate-300 hover:text-white px-3 py-1.5 rounded-lg transition-all"
                                        id="btn-edit-copy-{{ $copy->id }}">
                                        Edit
                                    </button>
                                    {{-- Delete Form --}}
                                    <form
                                        action="{{ route('books.copies.destroy', [$bookModel->id, $copy->id]) }}"
                                        method="POST"
                                        onsubmit="return confirm('Delete copy COPY-{{ str_pad($copy->id, 5, '0', STR_PAD_LEFT) }}? This cannot be undone.')"
                                        id="form-delete-copy-{{ $copy->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-xs bg-red-600/10 hover:bg-red-600/20 border border-red-500/20 text-red-400 hover:text-red-300 px-3 py-1.5 rounded-lg transition-all">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Back link --}}
    <div>
        <a href="{{ route('books.show', $bookModel->id) }}"
           class="text-sm text-slate-500 hover:text-slate-300 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Book
        </a>
    </div>
</div>

{{-- ─── Add Copy Modal ─────────────────────────────────────────────────────── --}}
@auth
    @if(in_array(auth()->user()->role->value, ['ADMIN','LIBRARIAN']))
        <div id="add-copy-modal"
             class="hidden fixed inset-0 z-50 flex items-center justify-center px-4"
             onclick="if(event.target===this) this.classList.add('hidden')">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative bg-slate-900 border border-slate-700/50 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white">Add Physical Copy</h2>
                    <button onclick="document.getElementById('add-copy-modal').classList.add('hidden')"
                            class="text-slate-500 hover:text-slate-300 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('books.copies.store', $bookModel->id) }}" method="POST" class="space-y-4"
                      id="form-add-copy">
                    @csrf
                    <div>
                        <label for="add-status" class="block text-sm font-medium text-slate-300 mb-2">Initial Status</label>
                        <select name="status" id="add-status"
                                class="w-full bg-slate-800 border border-slate-600/50 text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 transition">
                            <option value="AVAILABLE" class="bg-slate-900 text-slate-100">AVAILABLE</option>
                            <option value="BORROWED" class="bg-slate-900 text-slate-100">BORROWED</option>
                            <option value="RESERVED" class="bg-slate-900 text-slate-100">RESERVED</option>
                            <option value="LOST" class="bg-slate-900 text-slate-100">LOST</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button"
                                onclick="document.getElementById('add-copy-modal').classList.add('hidden')"
                                class="flex-1 text-sm bg-slate-800 hover:bg-slate-700 border border-slate-600/50 text-slate-300 px-4 py-2.5 rounded-xl transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 text-sm bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-4 py-2.5 rounded-xl transition-all"
                                id="btn-submit-add-copy">
                            Add Copy
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ─── Edit Copy Modal ──────────────────────────────────────────────── --}}
        <div id="edit-copy-modal"
             class="hidden fixed inset-0 z-50 flex items-center justify-center px-4"
             onclick="if(event.target===this) this.classList.add('hidden')">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative bg-slate-900 border border-slate-700/50 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white" id="edit-modal-title">Edit Copy</h2>
                    <button onclick="document.getElementById('edit-copy-modal').classList.add('hidden')"
                            class="text-slate-500 hover:text-slate-300 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="edit-copy-form" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit-status" class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                        <select name="status" id="edit-status"
                                class="w-full bg-slate-800 border border-slate-600/50 text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 transition">
                            <option value="AVAILABLE" class="bg-slate-900 text-slate-100">AVAILABLE</option>
                            <option value="BORROWED" class="bg-slate-900 text-slate-100">BORROWED</option>
                            <option value="RESERVED" class="bg-slate-900 text-slate-100">RESERVED</option>
                            <option value="LOST" class="bg-slate-900 text-slate-100">LOST</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button"
                                onclick="document.getElementById('edit-copy-modal').classList.add('hidden')"
                                class="flex-1 text-sm bg-slate-800 hover:bg-slate-700 border border-slate-600/50 text-slate-300 px-4 py-2.5 rounded-xl transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 text-sm bg-amber-600 hover:bg-amber-500 text-white font-medium px-4 py-2.5 rounded-xl transition-all"
                                id="btn-submit-edit-copy">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endauth

<script>
function openEditModal(copyId, currentStatus) {
    const modal  = document.getElementById('edit-copy-modal');
    const form   = document.getElementById('edit-copy-form');
    const select = document.getElementById('edit-status');
    const title  = document.getElementById('edit-modal-title');

    const bookId = {{ $bookModel->id }};
    form.action  = `/books/${bookId}/copies/${copyId}`;
    select.value = currentStatus;
    title.textContent = `Edit Copy #${copyId}`;

    modal.classList.remove('hidden');
}
</script>
@endsection

<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreReservationRequest;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService,
    ) {}

    // ─── GET /reservations ─────────────────────────────────────────────────────

    /**
     * List reservations.
     * Admin/Librarian: all reservations.
     * Member: their own reservations only.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        if (in_array($user->role->value, [Role::ADMIN->value, Role::LIBRARIAN->value])) {
            $reservations = $this->reservationService->listAll();
        } else {
            $reservations = $this->reservationService->listForUser($user);
        }

        return view('reservations.index', compact('reservations'));
    }

    // ─── POST /reservations ────────────────────────────────────────────────────

    /**
     * Create a new reservation.
     */
    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $reservation = $this->reservationService->reserve(
            auth()->user(),
            (int) $request->validated('book_id'),
        );

        return redirect()
            ->route('reservations.show', $reservation->id)
            ->with('success', 'You have been added to the reservation queue. Your position: #' . $reservation->queue_position);
    }

    // ─── GET /reservations/{id} ────────────────────────────────────────────────

    /**
     * Show a single reservation.
     * Members may only view their own; admin/librarian may view any.
     */
    public function show(int $id): View
    {
        $reservation = $this->reservationService->findOrFail($id);
        $user        = auth()->user();

        if (
            ! in_array($user->role->value, [Role::ADMIN->value, Role::LIBRARIAN->value])
            && $reservation->user_id !== $user->id
        ) {
            abort(403, 'You are not authorized to view this reservation.');
        }

        return view('reservations.show', compact('reservation'));
    }

    // ─── PATCH /reservations/{id}/cancel ──────────────────────────────────────

    /**
     * Cancel a PENDING reservation.
     * Members may only cancel their own; admin/librarian may cancel any.
     */
    public function cancel(int $id): RedirectResponse
    {
        $reservation = $this->reservationService->findOrFail($id);
        $user        = auth()->user();

        if (
            ! in_array($user->role->value, [Role::ADMIN->value, Role::LIBRARIAN->value])
            && $reservation->user_id !== $user->id
        ) {
            abort(403, 'You are not authorized to cancel this reservation.');
        }

        $this->reservationService->cancel($reservation);

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation has been cancelled successfully.');
    }

    // ─── GET /books/{id}/reservations ─────────────────────────────────────────

    /**
     * Show all reservations for a specific book (admin/librarian only).
     */
    public function bookReservations(int $id): View
    {
        $user = auth()->user();

        if (! in_array($user->role->value, [Role::ADMIN->value, Role::LIBRARIAN->value])) {
            abort(403, 'Only administrators and librarians can view book reservation queues.');
        }

        $book         = $this->reservationService->findBookOrFail($id);
        $reservations = $this->reservationService->listForBook($book);
        $queue        = $this->reservationService->pendingQueueForBook($book);

        return view('reservations.book', compact('book', 'reservations', 'queue'));
    }
}

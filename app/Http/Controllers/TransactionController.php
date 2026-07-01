<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\BorrowRequest;
use App\Http\Requests\ExtendRequest;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    // ─── GET /transactions ────────────────────────────────────────────────────

    /**
     * Display transactions:
     *  - Admins / Librarians see all transactions.
     *  - Members see only their own.
     */
    public function index(): View
    {
        $user = Auth::user();

        if ($user->role === Role::ADMIN || $user->role === Role::LIBRARIAN) {
            $transactions = $this->transactionService->listAll();
        } else {
            $transactions = $this->transactionService->listForUser($user);
        }

        return view('transactions.index', compact('transactions'));
    }

    // ─── GET /transactions/{id} ───────────────────────────────────────────────

    public function show(int $id): View
    {
        $transaction = $this->transactionService->findOrFail($id);

        $this->authorizeView($transaction);

        return view('transactions.show', compact('transaction'));
    }

    // ─── POST /transactions/borrow ────────────────────────────────────────────

    public function borrow(BorrowRequest $request): RedirectResponse
    {
        $transaction = $this->transactionService->borrow(
            Auth::user(),
            $request->validated()['copy_id']
        );

        return redirect()
            ->route('transactions.show', $transaction->id)
            ->with('success', 'Book borrowed successfully! Due date: ' . $transaction->due_date->format('d M Y'));
    }

    // ─── PATCH /transactions/{id}/return ─────────────────────────────────────

    public function return(int $id): RedirectResponse
    {
        $transaction = $this->transactionService->findOrFail($id);

        $this->authorizeOwnerOrAdmin($transaction);

        $transaction = $this->transactionService->returnCopy($transaction);

        $message = 'Book returned successfully.';
        if ($transaction->fine_amount > 0) {
            $message .= ' Fine incurred: ' . number_format($transaction->fine_amount) . ' (overdue).';
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', $message);
    }

    // ─── PATCH /transactions/{id}/extend ─────────────────────────────────────

    public function extend(ExtendRequest $request, int $id): RedirectResponse
    {
        $transaction = $this->transactionService->findOrFail($id);

        $this->authorizeOwnerOrAdmin($transaction);

        $days = $request->validated()['days'] ?? null;
        $transaction = $this->transactionService->extend($transaction, $days);

        return redirect()
            ->route('transactions.show', $transaction->id)
            ->with('success', 'Loan extended! New due date: ' . $transaction->due_date->format('d M Y'));
    }

    // ─── GET /transactions/overdue ────────────────────────────────────────────

    public function overdue(): View
    {
        $this->authorizeAdmin();

        $transactions = $this->transactionService->listOverdue();

        return view('transactions.overdue', compact('transactions'));
    }

    // ─── Private Authorization Helpers ────────────────────────────────────────

    private function authorizeAdmin(): void
    {
        $role = Auth::user()?->role;

        if ($role !== Role::ADMIN && $role !== Role::LIBRARIAN) {
            abort(403, 'Unauthorized.');
        }
    }

    private function authorizeOwnerOrAdmin(mixed $transaction): void
    {
        $user = Auth::user();

        if (
            $user->role !== Role::ADMIN
            && $user->role !== Role::LIBRARIAN
            && $transaction->user_id !== $user->id
        ) {
            abort(403, 'Unauthorized.');
        }
    }

    private function authorizeView(mixed $transaction): void
    {
        $user = Auth::user();

        if (
            $user->role !== Role::ADMIN
            && $user->role !== Role::LIBRARIAN
            && $transaction->user_id !== $user->id
        ) {
            abort(403, 'Unauthorized.');
        }
    }
}

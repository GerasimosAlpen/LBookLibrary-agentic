<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreBookCopyRequest;
use App\Http\Requests\UpdateBookCopyRequest;
use App\Services\BookCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookCopyController extends Controller
{
    public function __construct(
        private readonly BookCopyService $copyService,
    ) {}

    // ─── GET /books/{id}/copies ───────────────────────────────────────────────

    public function index(int $book): View
    {
        $copies       = $this->copyService->listCopies($book);
        $bookModel    = $this->copyService->resolveBook($book);
        $availability = $this->copyService->availability($book);

        return view('catalog.books.copies.index', compact('bookModel', 'copies', 'availability'));
    }

    // ─── POST /books/{id}/copies ──────────────────────────────────────────────

    public function store(StoreBookCopyRequest $request, int $book): RedirectResponse
    {
        $this->authorizeManage();

        $this->copyService->createCopy($book, $request->validated());

        return redirect()
            ->route('books.copies.index', $book)
            ->with('success', 'Book copy added successfully.');
    }

    // ─── PUT /books/{id}/copies/{copyId} ─────────────────────────────────────

    public function update(UpdateBookCopyRequest $request, int $book, int $copy): RedirectResponse
    {
        $this->authorizeManage();

        $this->copyService->updateCopy($book, $copy, $request->validated());

        return redirect()
            ->route('books.copies.index', $book)
            ->with('success', 'Copy status updated successfully.');
    }

    // ─── DELETE /books/{id}/copies/{copyId} ──────────────────────────────────

    public function destroy(int $book, int $copy): RedirectResponse
    {
        $this->authorizeManage();

        $this->copyService->deleteCopy($book, $copy);

        return redirect()
            ->route('books.copies.index', $book)
            ->with('success', 'Book copy deleted successfully.');
    }

    // ─── GET /books/{id}/availability ─────────────────────────────────────────

    public function availability(int $book): JsonResponse
    {
        $data = $this->copyService->availability($book);

        return response()->json($data);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function authorizeManage(): void
    {
        $role = Auth::user()?->role;
        if ($role !== Role::ADMIN && $role !== Role::LIBRARIAN) {
            abort(403, 'Unauthorized.');
        }
    }
}

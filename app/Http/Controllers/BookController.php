<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Services\AuthorService;
use App\Services\BookService;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly AuthorService $authorService,
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request): View
    {
        $books = $this->bookService->list(
            search:     $request->input('search'),
            categoryId: $request->integer('category') ?: null,
            sortBy:     $request->input('sort_by', 'title'),
            sortDir:    $request->input('sort_dir', 'asc'),
        );

        $categories = $this->categoryService->all();

        return view('catalog.books.index', compact('books', 'categories'));
    }

    public function show(int $book): View
    {
        $book = $this->bookService->findOrFail($book);
        return view('catalog.books.show', compact('book'));
    }

    public function create(): View
    {
        $this->authorizeManage();

        $authors    = $this->authorService->all();
        $categories = $this->categoryService->all();

        return view('catalog.books.create', compact('authors', 'categories'));
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $book = $this->bookService->create($request->validated());

        return redirect()
            ->route('books.show', $book->id)
            ->with('success', 'Book created successfully.');
    }

    public function edit(int $book): View
    {
        $this->authorizeManage();

        $book       = $this->bookService->findOrFail($book);
        $authors    = $this->authorService->all();
        $categories = $this->categoryService->all();

        return view('catalog.books.edit', compact('book', 'authors', 'categories'));
    }

    public function update(UpdateBookRequest $request, int $book): RedirectResponse
    {
        $updated = $this->bookService->update($book, $request->validated());

        return redirect()
            ->route('books.show', $updated->id)
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(int $book): RedirectResponse
    {
        $this->authorizeManage();

        $this->bookService->delete($book);

        return redirect()
            ->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }

    private function authorizeManage(): void
    {
        $role = Auth::user()?->role;
        if ($role !== Role::ADMIN && $role !== Role::LIBRARIAN) {
            abort(403, 'Unauthorized.');
        }
    }
}

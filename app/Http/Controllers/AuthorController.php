<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Services\AuthorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function __construct(
        private readonly AuthorService $authorService,
    ) {}

    public function index(): View
    {
        $authors = $this->authorService->list();
        return view('catalog.authors.index', compact('authors'));
    }

    public function show(int $author): View
    {
        $author = $this->authorService->findOrFail($author);
        $books  = $this->authorService->booksPaginate($author->id);

        return view('catalog.authors.show', compact('author', 'books'));
    }

    public function create(): View
    {
        $this->authorizeManage();
        return view('catalog.authors.create');
    }

    public function store(StoreAuthorRequest $request): RedirectResponse
    {
        $author = $this->authorService->create($request->validated());

        return redirect()
            ->route('authors.show', $author->id)
            ->with('success', 'Author created successfully.');
    }

    public function edit(int $author): View
    {
        $this->authorizeManage();

        $author = $this->authorService->findOrFail($author);
        return view('catalog.authors.edit', compact('author'));
    }

    public function update(UpdateAuthorRequest $request, int $author): RedirectResponse
    {
        $updated = $this->authorService->update($author, $request->validated());

        return redirect()
            ->route('authors.show', $updated->id)
            ->with('success', 'Author updated successfully.');
    }

    public function destroy(int $author): RedirectResponse
    {
        $this->authorizeManage();

        try {
            $this->authorService->delete($author);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return redirect()
                ->route('authors.show', $author)
                ->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('authors.index')
            ->with('success', 'Author deleted successfully.');
    }

    private function authorizeManage(): void
    {
        $role = Auth::user()?->role;
        if ($role !== Role::ADMIN && $role !== Role::LIBRARIAN) {
            abort(403, 'Unauthorized.');
        }
    }
}

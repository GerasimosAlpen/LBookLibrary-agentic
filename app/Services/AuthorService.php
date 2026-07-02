<?php

namespace App\Services;

use App\Models\Author;
use App\Repositories\AuthorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AuthorService
{
    public function __construct(
        private readonly AuthorRepository $authorRepository
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->authorRepository->paginate($perPage);
    }

    public function all(): Collection
    {
        return $this->authorRepository->all();
    }

    public function findOrFail(int $id): Author
    {
        $author = $this->authorRepository->findById($id);

        if ($author === null) {
            abort(404, 'Author not found.');
        }

        return $author;
    }

    public function create(array $data): Author
    {
        return $this->authorRepository->create($data);
    }

    public function update(int $id, array $data): Author
    {
        $author = $this->findOrFail($id);
        return $this->authorRepository->update($author, $data);
    }

    public function delete(int $id): void
    {
        $author = $this->findOrFail($id);

        if ($author->books()->exists()) {
            abort(422, 'Cannot delete an author who has associated books. Remove the books first.');
        }

        $this->authorRepository->delete($author);
    }

    public function booksPaginate(int $id, int $perPage = 12): LengthAwarePaginator
    {
        $author = $this->findOrFail($id);
        return $this->authorRepository->booksPaginate($author, $perPage);
    }
}

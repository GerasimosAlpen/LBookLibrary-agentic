<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('books.index'));

Route::middleware('guest')->group(function () {
    Route::get('/auth/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::get('/auth/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/auth/password', [AuthController::class, 'showUpdatePassword'])->name('auth.password');
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // ─── Catalog ──────────────────────────────────────────────────────────────

    // Books
    Route::get('/books',                [BookController::class, 'index'])->name('books.index');
    Route::get('/books/create',         [BookController::class, 'create'])->name('books.create');
    Route::post('/books',               [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}',         [BookController::class, 'show'])->name('books.show')->where('book', '[0-9]+');
    Route::get('/books/{book}/edit',    [BookController::class, 'edit'])->name('books.edit')->where('book', '[0-9]+');
    Route::put('/books/{book}',         [BookController::class, 'update'])->name('books.update')->where('book', '[0-9]+');
    Route::delete('/books/{book}',      [BookController::class, 'destroy'])->name('books.destroy')->where('book', '[0-9]+');

    // Authors
    Route::get('/authors',              [AuthorController::class, 'index'])->name('authors.index');
    Route::get('/authors/create',       [AuthorController::class, 'create'])->name('authors.create');
    Route::post('/authors',             [AuthorController::class, 'store'])->name('authors.store');
    Route::get('/authors/{author}',     [AuthorController::class, 'show'])->name('authors.show')->where('author', '[0-9]+');
    Route::get('/authors/{author}/edit',[AuthorController::class, 'edit'])->name('authors.edit')->where('author', '[0-9]+');
    Route::put('/authors/{author}',     [AuthorController::class, 'update'])->name('authors.update')->where('author', '[0-9]+');
    Route::delete('/authors/{author}',  [AuthorController::class, 'destroy'])->name('authors.destroy')->where('author', '[0-9]+');

    // Categories
    Route::get('/categories',                [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create',         [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories',               [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}',     [CategoryController::class, 'show'])->name('categories.show')->where('category', '[0-9]+');
    Route::get('/categories/{category}/edit',[CategoryController::class, 'edit'])->name('categories.edit')->where('category', '[0-9]+');
    Route::put('/categories/{category}',     [CategoryController::class, 'update'])->name('categories.update')->where('category', '[0-9]+');
    Route::delete('/categories/{category}',  [CategoryController::class, 'destroy'])->name('categories.destroy')->where('category', '[0-9]+');

    // ─── Inventory ────────────────────────────────────────────────────────────

    // Book Copies
    Route::get('/books/{book}/copies',              [BookCopyController::class, 'index'])->name('books.copies.index')->where('book', '[0-9]+');
    Route::post('/books/{book}/copies',             [BookCopyController::class, 'store'])->name('books.copies.store')->where('book', '[0-9]+');
    Route::put('/books/{book}/copies/{copy}',       [BookCopyController::class, 'update'])->name('books.copies.update')->where(['book' => '[0-9]+', 'copy' => '[0-9]+']);
    Route::delete('/books/{book}/copies/{copy}',    [BookCopyController::class, 'destroy'])->name('books.copies.destroy')->where(['book' => '[0-9]+', 'copy' => '[0-9]+']);

    // Book Availability
    Route::get('/books/{book}/availability',        [BookCopyController::class, 'availability'])->name('books.availability')->where('book', '[0-9]+');
});

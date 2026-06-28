<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Author;
use App\Models\Category;
use App\Models\Book;
use App\Enums\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Users (idempotent with updateOrCreate)
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
                'role'     => Role::ADMIN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'librarian@example.com'],
            [
                'name'     => 'Librarian User',
                'password' => Hash::make('password'),
                'role'     => Role::LIBRARIAN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'name'     => 'Member User',
                'password' => Hash::make('password'),
                'role'     => Role::MEMBER,
            ]
        );

        // Generate 10 additional random members if they don't exist
        if (User::where('role', Role::MEMBER)->count() < 11) {
            User::factory(10)->create(['role' => Role::MEMBER]);
        }

        // 2. Create Core/Realistic Categories (idempotent with firstOrCreate)
        $fiction    = Category::firstOrCreate(['name' => 'Fiction']);
        $sciFi      = Category::firstOrCreate(['name' => 'Science Fiction']);
        $mystery    = Category::firstOrCreate(['name' => 'Mystery & Thriller']);
        $history    = Category::firstOrCreate(['name' => 'History']);
        $tech       = Category::firstOrCreate(['name' => 'Technology']);
        $biography  = Category::firstOrCreate(['name' => 'Biography']);
        $philosophy = Category::firstOrCreate(['name' => 'Philosophy']);

        $categoriesCollection = collect([$fiction, $sciFi, $mystery, $history, $tech, $biography, $philosophy]);

        // 3. Seed Realistic Authors & Books (idempotent with firstOrCreate / updateOrCreate)
        $orwell = Author::firstOrCreate(
            ['name' => 'George Orwell'],
            ['bio'  => 'Eric Arthur Blair, better known by his pen name George Orwell, was an English novelist, essayist, journalist, and critic.']
        );

        $book1 = Book::updateOrCreate(
            ['isbn' => '978-0451524935'],
            [
                'title'          => '1984',
                'description'    => 'A dystopian social science fiction novel and cautionary tale about totalitarianism, mass surveillance, and repressive regimentation.',
                'published_year' => 1949,
                'author_id'      => $orwell->id,
            ]
        );
        $book1->categories()->syncWithoutDetaching([$fiction->id, $sciFi->id]);

        $book2 = Book::updateOrCreate(
            ['isbn' => '978-0451526342'],
            [
                'title'          => 'Animal Farm',
                'description'    => 'A beast fable, in the form of a satirical allegorical novella, which tells the story of a group of farm animals who rebel against their human farmer.',
                'published_year' => 1945,
                'author_id'      => $orwell->id,
            ]
        );
        $book2->categories()->syncWithoutDetaching([$fiction->id]);

        $tolkien = Author::firstOrCreate(
            ['name' => 'J.R.R. Tolkien'],
            ['bio'  => 'John Ronald Reuel Tolkien was an English writer, poet, philologist, and academic, best known as the author of the high fantasy works The Hobbit and The Lord of the Rings.']
        );

        $book3 = Book::updateOrCreate(
            ['isbn' => '978-0261102217'],
            [
                'title'          => 'The Hobbit',
                'description'    => 'A fantasy novel that follows the quest of home-loving hobbit Bilbo Baggins to win a share of the treasure guarded by Smaug the dragon.',
                'published_year' => 1937,
                'author_id'      => $tolkien->id,
            ]
        );
        $book3->categories()->syncWithoutDetaching([$fiction->id]);

        $rowling = Author::firstOrCreate(
            ['name' => 'J.K. Rowling'],
            ['bio'  => 'Joanne Rowling, author of the Harry Potter series, which has won multiple awards and sold more than 500 million copies.']
        );

        $book4 = Book::updateOrCreate(
            ['isbn' => '978-0747532699'],
            [
                'title'          => 'Harry Potter and the Philosopher\'s Stone',
                'description'    => 'A fantasy novel that chronicles the lives of a young wizard, Harry Potter, and his friends Hermione Granger and Ron Weasley.',
                'published_year' => 1997,
                'author_id'      => $rowling->id,
            ]
        );
        $book4->categories()->syncWithoutDetaching([$fiction->id]);

        // 4. Seed Random Data only if database is relatively empty (prevents bloating on repeated runs)
        if (Book::count() < 10) {
            Author::factory(12)->create()->each(function ($author) use ($categoriesCollection) {
                Book::factory(rand(1, 3))->create([
                    'author_id' => $author->id,
                ])->each(function ($book) use ($categoriesCollection) {
                    // Attach 1 to 2 random categories
                    $book->categories()->syncWithoutDetaching(
                        $categoriesCollection->random(rand(1, 2))->pluck('id')->toArray()
                    );
                });
            });
        }
    }
}

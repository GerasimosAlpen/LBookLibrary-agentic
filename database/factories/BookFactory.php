<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'          => fake()->sentence(3),
            'description'    => fake()->paragraph(),
            'isbn'           => fake()->unique()->isbn13(),
            'published_year' => fake()->year(),
            'author_id'      => Author::factory(),
        ];
    }
}

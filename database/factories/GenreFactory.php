<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Genre>
 */
class GenreFactory extends Factory
{
    /**
     * Curated list of common film genres (Indonesian-flavored).
     */
    private const GENRES = [
        'Aksi', 'Drama', 'Komedi', 'Horor', 'Thriller', 'Romance',
        'Fiksi Ilmiah', 'Fantasi', 'Animasi', 'Dokumenter',
        'Misteri', 'Petualangan', 'Biografi', 'Musikal', 'Perang',
    ];

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(self::GENRES);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}

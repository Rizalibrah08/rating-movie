<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    private static array $posters = [
        '/image/card/cars.jpg',
        '/image/card/despicable-me-3.jpg',
        '/image/card/home-alone.jpg',
        '/image/card/kung-fu-panda.jpg',
        '/image/card/ratatouille.jpg',
        '/image/card/up.jpg',
        '/image/card/world-war-z.jpg',
    ];

    private static string $heroBackdrop = '/image/hero/the-boys.jpg';
    private static int $posterIndex = 0;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(rand(2, 5));
        $title = rtrim($title, '.');

        $posterUrl = self::$posters[self::$posterIndex % count(self::$posters)];
        self::$posterIndex++;

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title),
            'synopsis' => $this->faker->paragraph(rand(2, 4)),
            'year' => $this->faker->numberBetween(1990, (int) date('Y')),
            'duration_min' => $this->faker->numberBetween(80, 210),
            'director' => $this->faker->name(),
            'poster_path' => null,
            'poster_url' => $posterUrl,
            'backdrop_path' => null,
            'backdrop_url' => self::$heroBackdrop,
        ];
    }

    public function withBackdrop(): static
    {
        return $this->state(fn () => ['backdrop_url' => self::$heroBackdrop, 'backdrop_path' => null]);
    }

    public function withoutBackdrop(): static
    {
        return $this->state(fn () => ['backdrop_url' => null, 'backdrop_path' => null]);
    }
}

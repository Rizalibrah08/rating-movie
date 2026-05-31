<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'movie_id' => Movie::factory(),
            'rating' => $this->faker->numberBetween(0, 100),
            'body' => $this->faker->paragraph(rand(2, 5)),
            'status' => Review::STATUS_PUBLISHED,
            'ip' => $this->faker->ipv4(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => Review::STATUS_PUBLISHED]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Review::STATUS_PENDING]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => Review::STATUS_REJECTED]);
    }

    public function highScore(): static
    {
        return $this->state(fn () => ['rating' => $this->faker->numberBetween(75, 100)]);
    }

    public function midScore(): static
    {
        return $this->state(fn () => ['rating' => $this->faker->numberBetween(50, 74)]);
    }

    public function lowScore(): static
    {
        return $this->state(fn () => ['rating' => $this->faker->numberBetween(0, 49)]);
    }
}

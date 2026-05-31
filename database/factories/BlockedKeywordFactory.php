<?php

namespace Database\Factories;

use App\Models\BlockedKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedKeyword>
 */
class BlockedKeywordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'keyword' => $this->faker->unique()->word(),
            'category' => $this->faker->randomElement(BlockedKeyword::CATEGORIES),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function category(string $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}

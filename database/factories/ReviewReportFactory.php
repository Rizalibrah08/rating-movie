<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewReport>
 */
class ReviewReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'reporter_id' => User::factory(),
            'reason' => $this->faker->randomElement(ReviewReport::REASONS),
            'note' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'status' => ReviewReport::STATUS_PENDING,
        ];
    }
}

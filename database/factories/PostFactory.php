<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'title' => fake()->sentence(8),
            'body' => fake()->paragraphs(3, true),
            'status' => 'open',
            'view_count' => fake()->numberBetween(0, 500),
            'vote_score' => fake()->numberBetween(-10, 100),
            'is_answered' => false,
        ];
    }
}

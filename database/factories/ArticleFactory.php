<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'ART-'.fake()->unique()->numerify('#####'),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'unit_price' => fake()->numberBetween(5, 500) * 1000,
            'tax_rate' => 18,
            'unit' => fake()->randomElement(['unité', 'heure', 'jour', 'forfait']),
            'is_active' => true,
        ];
    }
}

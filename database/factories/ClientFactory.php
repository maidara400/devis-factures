<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => '+221 77 '.fake()->numerify('### ## ##'),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Dakar', 'Thiès', 'Saint-Louis', 'Mbour', 'Ziguinchor']),
            'country' => 'Sénégal',
            'tax_number' => fake()->numerify('#########'),
        ];
    }
}

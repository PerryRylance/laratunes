<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Track>
 */
class TrackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plays = $this->faker->numberBetween(0, 10);

        if($plays === 0)
            $lastPlayedAt = null;
        else
            $lastPlayedAt = $this->faker->dateTimeThisMonth();

        return [
            'title' => fake()->words(3, true),
			'artist' => fake()->firstName() . " " . fake()->lastName(),
			'hash' => md5( fake()->password() ),
			'path' => '/tmp/' . fake()->uuid() . '.mp3',
            'plays' => $plays,
            'last_played_at' => $lastPlayedAt
        ];
    }
}

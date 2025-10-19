<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

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

        $src = fake()->randomElement([
            '8-bit-takeover-367276.mp3',
            'chiptune-techno-electro-bubblegum-bass-bass-music-hiphop-1-334458.mp3',
            'pixelate-pixelated-dreams-313358.mp3'
        ]);
        
        $dst = fake()->uuid() . '.mp3';

        Storage::disk('media')->put($dst, file_get_contents("./tests/Fixtures/media/$src"));

        return [
            'title' => fake()->words(3, true),
			'artist' => fake()->firstName() . " " . fake()->lastName(),
			'path' => $dst,
            'plays' => $plays,
            'last_played_at' => $lastPlayedAt
        ];
    }
}

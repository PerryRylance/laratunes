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

        return [
            'title' => fake()->words(3, true),
			'artist' => fake()->firstName() . " " . fake()->lastName(),
			'path' => '/fake/path-' . fake()->uuid() . '.mp3',
            'plays' => $plays,
            'last_played_at' => $lastPlayedAt
        ];
    }

    public function uploaded(?string $filename = null): Factory
    {
        if($filename === null)
            $src = fake()->randomElement([
                '8-bit-takeover-367276.mp3',
                'chiptune-techno-electro-bubblegum-bass-bass-music-hiphop-1-334458.mp3',
                'pixelate-pixelated-dreams-313358.mp3'
            ]);
        else
            $src = $filename;
        
        $dst = fake()->uuid() . '.mp3';

        Storage::disk('media')->put($dst, file_get_contents("./tests/Fixtures/media/$src"));

        return $this->state(fn (array $attributes) => [
            'path' => $dst
        ]);
    }

    public function unplayed(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'plays' => 0,
            'last_played_at' => null
        ]);
    }
}

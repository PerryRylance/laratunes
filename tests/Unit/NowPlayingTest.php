<?php

namespace Tests\Unit;

use App\Facades\NowPlaying;
use App\Facades\Olaf;
use App\Models\Track;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NowPlayingTest extends TestCase
{
	public function testServiceIsMocked(): void
	{
		NowPlaying::fake();
		Storage::fake();
		Olaf::fake();

		$expected = Track::factory()->uploaded()->create();
		$expected->update([
			'hash' => '925bf0783aa48446bfe8181686525b6e',
		]);

		$actual = NowPlaying::track();

		$this->assertTrue($actual->is($expected));
	}
}

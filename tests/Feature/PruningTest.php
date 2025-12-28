<?php

namespace Tests\Feature;

use App\Models\Track;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruningTest extends TestCase
{
	public function testPruneOrphanedRecord(): void
	{
		$path = 'test.mp3';
        $contents = 'test';
        $hash = md5($contents);

        Storage::disk('media')->put($path, $contents);

		Track::factory()->create([
			'path' => $path
		]);

        Storage::disk('media')->delete($path);

		$this->artisan('app:prune-media');

		$this->assertDatabaseMissing(Track::getTableName(), [
			'path' => $path,
            'hash' => $hash
		]);
	}
}

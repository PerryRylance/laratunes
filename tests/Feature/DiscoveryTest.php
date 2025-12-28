<?php

namespace Tests\Feature;

use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\TestFiles;

class DiscoveryTest extends TestCase
{
	public function testDiscoverTracksCommand(): void
	{
        foreach(TestFiles::all() as $expected)
            Storage::disk('media')->put($expected, file_get_contents("./tests/Fixtures/media/$expected"));
        
		Artisan::call('app:discover-media');

		foreach(TestFiles::all() as $expected)
			$this->assertDatabaseHas(Track::getTableName(), [
				'hash' => md5(file_get_contents("./tests/Fixtures/media/$expected")),
				'path' => $expected
			]);
	}
}

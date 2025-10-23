<?php

namespace Tests\Feature;

use App\Facades\Fifo;
use App\Models\Track;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Fiber;
use Illuminate\Support\Facades\Storage;

class BroadcastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::disk('media')->put('loop.mp4', file_get_contents('./tests/Fixtures/media/loop.mp4'));
        Track::factory()->uploaded()->create();

        $fiber = new Fiber(function() {
            Artisan::call('app:start-broadcast');
        });

        $fiber->start();

        sleep(1);
    }

    public function testNowPlayingBufferCreated(): void
    {
        $this->assertFileExists('/buffers/now-playing');
    }

    public function testBufferHasData(): void
    {
        $this->assertGreaterThan(0, Fifo::usage());
    }
}

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
use Tests\Attributes\UsesRealStorage;

#[UsesRealStorage]
class BroadcastTest extends TestCase
{
    private InvokedProcess $process;

    protected function setUp(): void
    {
        parent::setUp();

        // NB: Because the tests run in a transaction, factories won't work here. So make sure there's some tracks to broadcast with.
        Process::run("php artisan app:reset-media");
        Process::run("php artisan app:discover-media --fake-olaf");

        $this->process = Process::start('php artisan app:start-broadcast', fn(string $type, string $output) => print("$type: $output" . PHP_EOL));

        // NB: Give it a second to get the buffer going
        sleep(5);
    }

    protected function tearDown(): void
    {
        $this->process->stop(10, SIGKILL);

        parent::tearDown();
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

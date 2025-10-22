<?php

namespace Tests\Feature;

use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    private ?InvokedProcess $process = null;

    private function startBroadcast(): void
    {
        $this->process = Process::start('php artisan app:broadcast');

        usleep(500_000);

        if(!$this->process->running())
        {
            $message = trim( $this->process->output() );

            $this->fail("Failed to start broadcast ($message)");
        }
    }

    protected function tearDown(): void
    {
        if($this->process)
        {
            $this->process->stop(1, SIGTERM);

            if($this->process->running())
            {
                $this->fail('Failed to stop broadcast, halting test suite');
                exit(1);
            }
        }

        parent::tearDown();
    }

    public function testNowPlayingBufferCreated(): void
    {
        $this->startBroadcast();
        $this->assertFileExists('/buffers/now-playing');
    }
}

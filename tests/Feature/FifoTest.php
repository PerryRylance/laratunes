<?php

namespace Tests\Feature;

use App\Facades\Fifo;
use Tests\TestCase;

class FifoTest extends TestCase
{
    public function testCreatedWithExpectedSize(): void
    {
        Fifo::create('/buffers/test');

        $this->assertEquals(1024 * 1024, Fifo::capacity());
    }
}

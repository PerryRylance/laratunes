<?php

namespace Tests\Unit;

use App\Facades\Olaf;
use Tests\TestCase;

class OlafTest extends TestCase
{
    public function testServiceIsMocked(): void
    {
        Olaf::fake();

        $results = Olaf::query('unused.mp3');
        $best = $results->items->first();

        $this->assertEquals(123, $best['confidence']);
        $this->assertEquals('fake.mp3', $best['file']);
    }
}

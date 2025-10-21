<?php

namespace Tests;

use App\Facades\Olaf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Tests\Attributes\UsesRealOlaf;
use ReflectionClass;
use Tests\Attributes\UsesRealStorage;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $reflect = new ReflectionClass($this);

        if(empty($reflect->getAttributes(UsesRealOlaf::class)))
            Olaf::fake();

        if(empty($reflect->getAttributes(UsesRealStorage::class)))
            Storage::fake('media');
    }
}

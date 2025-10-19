<?php

namespace Tests;

use App\Support\Olaf;

class OlafTestCase extends TestCase
{
    protected function afterRefreshingDatabase()
    {
        $olaf = new Olaf();
        $olaf->reset();
    }
}

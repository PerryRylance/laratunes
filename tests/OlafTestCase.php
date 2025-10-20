<?php

namespace Tests;

use App\Facades\Olaf;

class OlafTestCase extends TestCase
{
    protected function afterRefreshingDatabase()
    {
        Olaf::reset();
    }
}

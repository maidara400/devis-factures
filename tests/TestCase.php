<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les tests ne doivent pas dépendre d'un `npm run build` préalable.
        $this->withoutVite();
    }
}

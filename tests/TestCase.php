<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $this->verifierBaseTest();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->verifierBaseTest();
    }

    private function verifierBaseTest(): void
    {
        if (config('database.connections.mysql.database') !== 'oceapress_test') {
            throw new \RuntimeException('Les tests doivent utiliser oceapress_test');
        }
    }
}

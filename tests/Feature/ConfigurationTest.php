<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_environnement_d_exemple_desactive_le_debogage(): void
    {
        $contenu = file_get_contents(base_path('.env.example'));

        $this->assertNotFalse($contenu);
        $this->assertStringContainsString('APP_DEBUG=false', $contenu);
        $this->assertStringNotContainsString('APP_DEBUG=true', $contenu);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz del sistema no muestra nada por sí misma: siempre redirige
     * al dashboard (que a su vez exige login si no hay sesión).
     */
    public function test_la_raiz_redirige_al_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_la_pagina_de_login_carga_correctamente(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Unit;

use App\Models\ConteoFisico;
use Tests\TestCase;

/**
 * La fórmula de exactitud (H2 de la tesis) es el cálculo más importante
 * de todo el sistema: si está mal, toda la sustentación de esa hipótesis
 * se cae. Se prueba aislado, sin base de datos, sobre el propio modelo.
 */
class ConteoFisicoExactitudTest extends TestCase
{
    private function conteo(float $stockSistema, float $stockFisico): ConteoFisico
    {
        $conteo = new ConteoFisico([
            'stock_sistema' => $stockSistema,
            'stock_fisico'  => $stockFisico,
            'diferencia'    => $stockFisico - $stockSistema,
        ]);

        return $conteo;
    }

    public function test_conteo_exacto_da_100_por_ciento(): void
    {
        $this->assertSame(100.0, $this->conteo(50, 50)->exactitud);
    }

    public function test_un_faltante_reduce_la_exactitud_proporcionalmente(): void
    {
        // 100 en sistema, 95 contados a mano: 5% de diferencia -> 95% exactitud
        $this->assertSame(95.0, $this->conteo(100, 95)->exactitud);
    }

    public function test_un_sobrante_tambien_penaliza_la_exactitud(): void
    {
        // Un sobrante también es una imprecisión del inventario, no un acierto.
        $this->assertSame(90.0, $this->conteo(100, 110)->exactitud);
    }

    public function test_una_diferencia_mayor_al_stock_no_baja_de_cero(): void
    {
        $this->assertSame(0.0, $this->conteo(10, 100)->exactitud);
    }

    public function test_sistema_en_cero_y_conteo_en_cero_es_100_por_ciento(): void
    {
        $this->assertSame(100.0, $this->conteo(0, 0)->exactitud);
    }

    public function test_sistema_en_cero_pero_aparece_stock_fisico_es_0_por_ciento(): void
    {
        $this->assertSame(0.0, $this->conteo(0, 5)->exactitud);
    }
}

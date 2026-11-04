<?php

namespace Tests\Unit;

use App\Support\Cantidad;
use PHPUnit\Framework\TestCase;

class CantidadTest extends TestCase
{
    public function test_quita_ceros_de_sobra(): void
    {
        $this->assertSame('25', Cantidad::fmt('25.000'));
        $this->assertSame('0.5', Cantidad::fmt(0.5));
        $this->assertSame('1,250.75', Cantidad::fmt(1250.75));
        $this->assertSame('0', Cantidad::fmt(-0.0001));
    }

    public function test_pasa_a_la_unidad_mas_comoda(): void
    {
        $this->assertSame('500 mL', Cantidad::conUnidad(0.5, 'L'));
        $this->assertSame('1.5 L', Cantidad::conUnidad(1.5, 'L'));
        $this->assertSame('250 g', Cantidad::conUnidad(0.25, 'kg'));
        $this->assertSame('30 g', Cantidad::conUnidad(0.03, 'kg'));
        $this->assertSame('1.5 L', Cantidad::conUnidad(1500, 'mL'));
        $this->assertSame('2 kg', Cantidad::conUnidad(2000, 'g'));
        $this->assertSame('0 L', Cantidad::conUnidad(0, 'L'));
        $this->assertSame('-800 g', Cantidad::conUnidad(-0.8, 'kg'));
        $this->assertSame('+300 mL', Cantidad::conUnidad(0.3, 'L', true));
        $this->assertSame('12 und', Cantidad::conUnidad(12, 'und'));
    }
}

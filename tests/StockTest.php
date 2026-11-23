<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../funciones_stock.php';

class StockTest extends TestCase
{
    public function testEntradaSumaAlStock(): void
    {
        $this->assertSame(7, calcular_stock(5, 2, 'Entrada'));
    }

    public function testSalidaRestaAlStock(): void
    {
        $this->assertSame(3, calcular_stock(5, 2, 'Salida'));
    }

    public function testSalidaDeTodoElStockDejaCero(): void
    {
        $this->assertSame(0, calcular_stock(5, 5, 'Salida'));
    }

    public static function casosInvalidos(): array
    {
        return [
            'salida mayor al stock' => [1, 5, 'Salida'],
            'cantidad cero'         => [5, 0, 'Entrada'],
            'cantidad negativa'     => [5, -3, 'Salida'],
            'tipo desconocido'      => [5, 2, 'Traspaso'],
            'stock negativo'        => [-1, 2, 'Entrada'],
        ];
    }

    #[DataProvider('casosInvalidos')]
    public function testDatosInvalidosLanzanExcepcion(int $stock, int $cantidad, string $tipo): void
    {
        $this->expectException(InvalidArgumentException::class);
        calcular_stock($stock, $cantidad, $tipo);
    }
}
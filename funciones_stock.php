<?php

function calcular_stock(int $stock_actual, int $cantidad, string $tipo): int
{
    if ($stock_actual < 0) {
        throw new InvalidArgumentException('El stock actual no puede ser negativo');
    }
    if ($cantidad <= 0) {
        throw new InvalidArgumentException('La cantidad debe ser mayor a 0');
    }

    switch ($tipo) {
        case 'Entrada':
            return $stock_actual + $cantidad;
        case 'Salida':
            if ($cantidad > $stock_actual) {
                throw new InvalidArgumentException('Stock insuficiente');
            }
            return $stock_actual - $cantidad;
        default:
            throw new InvalidArgumentException('Tipo de movimiento inválido');
    }
}
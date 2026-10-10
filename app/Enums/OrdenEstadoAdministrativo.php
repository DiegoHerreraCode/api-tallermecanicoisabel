<?php

namespace App\Enums;

enum OrdenEstadoAdministrativo: string
{
    case PENDIENTE_PAGO = 'pendiente_pago';
    case PARCIALMENTE_PAGADA = 'parcialmente_pagada';
    case PAGADA = 'pagada';

    public static function values(): array
    {
        return array_map(fn($enum) => $enum->value, self::cases());
    }
}

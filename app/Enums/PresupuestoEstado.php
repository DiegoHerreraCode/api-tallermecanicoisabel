<?php

namespace App\Enums;

enum PresupuestoEstado: string
{
    case PENDIENTE = 'pendiente';
    case ACEPTADO = 'aceptado';
    case RECHAZADO = 'rechazado';
    case ANULADO = 'anulado';

    public static function values(): array
    {
        return array_map(fn($enum) => $enum->value, self::cases());
    }
}

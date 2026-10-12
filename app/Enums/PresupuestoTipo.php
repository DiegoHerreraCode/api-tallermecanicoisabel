<?php

namespace App\Enums;

enum PresupuestoTipo: string
{
    case INICIAL = 'inicial';
    case EXTRA = 'extra';

    public static function values(): array
    {
        return array_map(fn($enum) => $enum->value, self::cases());
    }
}

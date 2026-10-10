<?php

namespace App\Enums;

enum OrdenEstadoOperativo: string
{
    case EN_ESPERA = 'en_espera';
    case EN_PROCESO = 'en_proceso';
    case FINALIZADA = 'finalizada';
    case ANULADA = 'anulada';

    public static function values(): array
    {
        return array_map(fn($enum) => $enum->value, self::cases());
    }
}

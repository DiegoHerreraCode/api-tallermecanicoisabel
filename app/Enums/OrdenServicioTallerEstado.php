<?php

namespace App\Enums;

enum OrdenServicioTallerEstado: string
{
    case PENDIENTE_ASIGNACION = 'pendiente_asignacion';
    case EN_ESPERA = 'en_espera';
    case EN_PROCESO = 'en_proceso';
    case EN_PAUSA = 'en_pausa';
    case FINALIZADO = 'finalizado';
    case ANULADO = 'anulado';

    public static function values(): array
    {
        return array_map(fn($enum) => $enum->value, self::cases());
    }
}

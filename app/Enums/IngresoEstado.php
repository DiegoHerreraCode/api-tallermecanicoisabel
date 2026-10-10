<?php

namespace App\Enums;

enum IngresoEstado: string
{
    case CHECKLIST_PENDIENTE = 'checklist_pendiente';
    case ESPERANDO_ORDEN = 'esperando_orden';
    case CONFIRMANDO_PRESUPUESTO = 'confirmando_presupuesto';
    case ORDEN_CREADA = 'orden_creada';
    case ANULADO = 'anulado';

    public static function values(): array
    {
        return array_map(fn($enum) => $enum->value, self::cases());
    }

    // equivalente a 
    //return [self::CHECKLIST_PENDIENTE->value, self::ESPERANDO_ORDEN->value, self::CONFIRMANDO_PRESUPUESTO->value, self::ORDEN_CREADA->value, self::ANULADO->value];
    //return ['checklist_pendiente', 'esperando_orden', 'confirmando_presupuesto', 'orden_creada', 'anulado'];
}

<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Ingreso
 * 
 * Almacena los ingresos de vehiculos en el taller.
 */
class Ingreso extends ApiModel
{
    protected $table = 'ingresos';
    protected $primaryKey = 'id_ingreso';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_tipo_orden',
        'id_vehiculo',
        'id_admin',
        'fecha_ingreso',
        'estado',
        'observaciones_cliente',
        'pdf_checklist',
        'last_update',
        'fecha_anulacion',
        'id_admin_anulacion',
        'motivo_anulacion'
    ];
}

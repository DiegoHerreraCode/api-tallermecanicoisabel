<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo ServicioTaller
 * 
 * Almacena los servicios realizados en taller.
 */
class ServicioTaller extends ApiModel
{
    protected $table = 'servicios_taller';
    protected $primaryKey = 'id_servicio_taller';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_tipo_servicio_taller',
        'nombre',
        'descripcion',
        'costo',
        'monto_venta',
        'utilidad',
        'porcentaje_ganancia',
        'is_deleted'
    ];

    public function tipoServicioTaller()
    {
        return $this->belongsTo(TipoServicioTaller::class, 'id_tipo_servicio_taller', 'id_tipo_servicio_taller');
    }

    public function ordenesServiciosTaller()
    {
        return $this->hasMany(OrdenServicioTaller::class, 'id_servicio_taller', 'id_servicio_taller');
    }

    public function presupuestosServiciosTaller()
    {
        return $this->hasMany(PresupuestoServicioTaller::class, 'id_servicio_taller', 'id_servicio_taller');
    }
}

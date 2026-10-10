<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo OrdenServicioTaller
 * 
 * Almacena el detalle de servicios asignados a una orden de trabajo.
 */
class OrdenServicioTaller extends ApiModel
{
    protected $table = 'ordenes_servicios_taller';
    protected $primaryKey = 'id_orden_servicio_taller';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'id_servicio_taller',
        'id_personal',
        'precio_unitario',
        'cantidad',
        'monto_total_linea',
        'estado',
        'fecha_inicio',
        'fecha_fin'
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'id_orden', 'id_orden');
    }

    public function servicioTaller()
    {
        return $this->belongsTo(ServicioTaller::class, 'id_servicio_taller', 'id_servicio_taller');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'id_personal', 'id_personal');
    }
}

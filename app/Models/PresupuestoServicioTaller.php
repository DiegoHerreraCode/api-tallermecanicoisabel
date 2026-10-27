<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo PresupuestoServicioTaller
 * 
 * Almacena el detalle de servicios asignados a un presupuesto.
 */
class PresupuestoServicioTaller extends ApiModel
{
    protected $table = 'presupuestos_servicios_taller';
    protected $primaryKey = 'id_presupuesto_servicio_taller';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_presupuesto',
        'id_servicio_taller',
        'costo_unitario_base',
        'precio_unitario',
        'porcentaje_ganancia',
        'cantidad',
        'monto_total_linea_base',
        'monto_total_linea'
    ];

    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class, 'id_presupuesto', 'id_presupuesto');
    }

    public function servicioTaller()
    {
        return $this->belongsTo(ServicioTaller::class, 'id_servicio_taller', 'id_servicio_taller');
    }
}

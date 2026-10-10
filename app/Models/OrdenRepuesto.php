<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo OrdenRepuesto
 * 
 * Almacena el detalle de repuestos asignados a una orden de trabajo.
 */
class OrdenRepuesto extends ApiModel
{
    protected $table = 'ordenes_repuestos';
    protected $primaryKey = 'id_orden_repuesto';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'id_repuesto',
        'precio_unitario',
        'cantidad',
        'monto_total_linea'
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'id_orden', 'id_orden');
    }

    public function repuesto()
    {
        return $this->belongsTo(Repuesto::class, 'id_repuesto', 'id_repuesto');
    }
}

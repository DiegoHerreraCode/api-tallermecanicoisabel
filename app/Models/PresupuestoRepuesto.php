<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo PresupuestoRepuesto
 * 
 * Almacena el detalle de repuestos asignados a un presupuesto.
 */
class PresupuestoRepuesto extends ApiModel
{
    protected $table = 'presupuestos_repuestos';
    protected $primaryKey = 'id_presupuesto_repuesto';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_presupuesto',
        'id_repuesto',
        'precio_unitario',
        'cantidad',
        'monto_total_linea'
    ];

    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class, 'id_presupuesto', 'id_presupuesto');
    }

    public function repuesto()
    {
        return $this->belongsTo(Repuesto::class, 'id_repuesto', 'id_repuesto');
    }
}

<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Repuesto
 * 
 * Almacena los repuestos utilizados en taller.
 */
class Repuesto extends ApiModel
{
    protected $table = 'repuestos';
    protected $primaryKey = 'id_repuesto';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_marca_repuesto',
        'nombre_modelo',
        'ultimo_costo_compra_unitario',
        'monto_venta',
        'utilidad',
        'porcentaje_ganancia'
    ];

    public function marca()
    {
        return $this->belongsTo(MarcaRepuesto::class, 'id_marca_repuesto', 'id_marca_repuesto');
    }

    public function ordenesRepuestos()
    {
        return $this->hasMany(OrdenRepuesto::class, 'id_repuesto', 'id_repuesto');
    }

    public function presupuestosRepuestos()
    {
        return $this->hasMany(PresupuestoRepuesto::class, 'id_repuesto', 'id_repuesto');
    }
}

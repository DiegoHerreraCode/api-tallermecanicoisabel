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
}

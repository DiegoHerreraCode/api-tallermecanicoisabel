<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Orden
 * 
 * Almacena las ordenes de trabajo en el sistema.
 */
class Orden extends ApiModel
{
    protected $table = 'ordenes';
    protected $primaryKey = 'id_orden';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_ingreso',
        'id_tipo_orden',
        'id_cliente',
        'fecha_creacion',
        'fecha_fin',
        'fecha_fin_tentativa',
        'estado_operativo',
        'estado_administrativo',
        'monto_subtotal',
        'porcentaje_iva',
        'monto_iva',
        'monto_total',
        'monto_pendiente',
        'last_update',
        'fecha_anulacion',
        'id_admin_anulacion',
        'motivo_anulacion'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function tipoOrden()
    {
        return $this->belongsTo(TipoOrden::class, 'id_tipo_orden', 'id_tipo_orden');
    }

    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'id_ingreso', 'id_ingreso');
    }

    public function repuestos()
    {
        return $this->hasMany(OrdenRepuesto::class, 'id_orden', 'id_orden');
    }

    public function serviciosTaller()
    {
        return $this->hasMany(OrdenServicioTaller::class, 'id_orden', 'id_orden');
    }
}

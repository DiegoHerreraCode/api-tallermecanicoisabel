<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Presupuesto
 * 
 * Almacena los presupuestos generados en el sistema.
 */
class Presupuesto extends ApiModel
{
    protected $table = 'presupuestos';
    protected $primaryKey = 'id_presupuesto';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'id_ingreso',
        'id_admin',
        'num_presupuesto_padre',
        'num_presupuesto',
        'tipo',
        'monto_subtotal',
        'porcentaje_iva',
        'monto_iva',
        'monto_total',
        'estado',
        'pdf_presupuesto',
        'fecha_creacion',
        'fecha_vencimiento',
        'fecha_anulacion',
        'id_admin_anulacion',
        'motivo_anulacion'
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'id_orden', 'id_orden');
    }

    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'id_ingreso', 'id_ingreso');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }

    public function adminAnulacion()
    {
        return $this->belongsTo(Admin::class, 'id_admin_anulacion', 'id_admin');
    }

    public function repuestos()
    {
        return $this->hasMany(PresupuestoRepuesto::class, 'id_presupuesto', 'id_presupuesto');
    }

    public function serviciosTaller()
    {
        return $this->hasMany(PresupuestoServicioTaller::class, 'id_presupuesto', 'id_presupuesto');
    }
}

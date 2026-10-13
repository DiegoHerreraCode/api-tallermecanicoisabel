<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo HallazgoTecnico
 * 
 * Almacena los hallazgos tecnicos realizados en taller.
 */
class HallazgoTecnico extends ApiModel
{
    protected $table = 'hallazgos_tecnicos';
    protected $primaryKey = 'id_hallazgo_tecnico';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'hallazgo',
        'detalle'
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'id_orden', 'id_orden');
    }
}

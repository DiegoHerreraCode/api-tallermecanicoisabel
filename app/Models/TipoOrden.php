<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo TipoOrden
 * 
 * Almacena los tipos de orden de servicio que se manejan en el taller.
 */
class TipoOrden extends ApiModel
{
    protected $table = 'tipos_ordenes';
    protected $primaryKey = 'id_tipo_orden';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];
}

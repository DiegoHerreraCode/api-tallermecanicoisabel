<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo TipoServicioTaller
 * 
 * Almacena los tipos de servicios que se realizan en el taller.
 */
class TipoServicioTaller extends ApiModel
{
    protected $table = 'tipos_servicios_taller';
    protected $primaryKey = 'id_tipo_servicio_taller';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];
}

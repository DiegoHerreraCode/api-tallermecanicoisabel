<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Vehiculo
 * 
 * Gestiona la información de los vehiculos.
 */
class Vehiculo extends ApiModel
{
    protected $table = 'vehiculos';
    protected $primaryKey = 'id_vehiculo';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_tipo_vehiculo',
        'id_modelo_vehiculo',
        'placa',
        'annio',
    ];
}


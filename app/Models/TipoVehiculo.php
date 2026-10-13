<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo TipoVehiculo
 * 
 * Almacena los tipos de vehiculos atendidos en taller.
 */
class TipoVehiculo extends ApiModel
{
    protected $table = 'tipos_vehiculos';
    protected $primaryKey = 'id_tipo_vehiculo';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'id_tipo_vehiculo', 'id_tipo_vehiculo');
    }
}

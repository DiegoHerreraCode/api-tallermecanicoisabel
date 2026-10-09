<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo ModeloVehiculo
 * 
 * Almacena los modelos de los vehiculos atendidos en taller.
 */
class ModeloVehiculo extends ApiModel
{
    protected $table = 'modelos_vehiculos';
    protected $primaryKey = 'id_modelo_vehiculo';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'id_marca_vehiculo',
    ];

    public function marca()
    {
        return $this->belongsTo(MarcaVehiculo::class, 'id_marca_vehiculo', 'id_marca_vehiculo');
    }
}

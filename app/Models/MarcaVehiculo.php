<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo MarcaVehiculo
 * 
 * Almacena las marcas de los vehiculos atendidos en taller.
 */
class MarcaVehiculo extends ApiModel
{
    protected $table = 'marcas_vehiculos';
    protected $primaryKey = 'id_marca_vehiculo';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    public function modelos()
    {
        return $this->hasMany(ModeloVehiculo::class, 'id_marca_vehiculo', 'id_marca_vehiculo');
    }
}

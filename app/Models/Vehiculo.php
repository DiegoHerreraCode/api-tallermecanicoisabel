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

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function modelo()
    {
        return $this->belongsTo(ModeloVehiculo::class, 'id_modelo_vehiculo', 'id_modelo_vehiculo');
    }

    public function tipoVehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class, 'id_tipo_vehiculo', 'id_tipo_vehiculo');
    }

    public function ingresos()
    {
        return $this->hasMany(Ingreso::class, 'id_vehiculo', 'id_vehiculo');
    }
}


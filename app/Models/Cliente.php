<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Cliente
 * 
 * Gestiona la informaci�n de contacto y fiscal de clientes.
 */
class Cliente extends ApiModel
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'cedula',
        'correo',
        'num_tlf',
        'direccion',
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'id_cliente', 'id_cliente');
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'id_cliente', 'id_cliente');
    }

    public function pagos()
    {
        return $this->hasMany(PagoCliente::class, 'id_cliente', 'id_cliente');
    }
}


<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Admin
 * 
 * Representa al personal administrativo del sistema que gestiona compras,
 * �rdenes y supervisa las operaciones de la empresa.
 */
class Admin extends ApiModel
{
    // Nombre exacto de la tabla en PostgreSQL
    protected $table = 'admins';

    // Clave primaria entera
    protected $primaryKey = 'id_admin';
    protected $keyType = 'int';
    public $incrementing = false; // Gestionado por la secuencia de HasGeneratedID
    public $timestamps = false;

    protected $hidden = [];

    // Atributos asignables masivamente
    protected $fillable = [
        'nombre',
        'cedula',
        'num_tlf',
        'direccion',
        'id_user',
    ];

    /**
     * Relaci�n con el usuario del sistema de autenticaci�n (users).
     * Consulta SQL Raw equivalente:
     * SELECT * FROM users WHERE id = admins.id_user LIMIT 1;
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function ingresos()
    {
        return $this->hasMany(Ingreso::class, 'id_admin', 'id_admin');
    }

    public function ingresosAnulados()
    {
        return $this->hasMany(Ingreso::class, 'id_admin_anulacion', 'id_admin');
    }

    public function ordenesAnuladas()
    {
        return $this->hasMany(Orden::class, 'id_admin_anulacion', 'id_admin');
    }

    public function presupuestos()
    {
        return $this->hasMany(Presupuesto::class, 'id_admin', 'id_admin');
    }

    public function presupuestosAnulados()
    {
        return $this->hasMany(Presupuesto::class, 'id_admin_anulacion', 'id_admin');
    }
}

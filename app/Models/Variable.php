<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo Variable
 * 
 * Gestiona las variables del sistema.
 */
class Variable extends ApiModel
{
    protected $table = 'variables';
    protected $primaryKey = 'id_variable';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'valor',
        'tipo'
    ];
}

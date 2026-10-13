<?php

namespace App\Models;

use App\Models\ApiModel;

/**
 * Modelo MarcaRepuesto
 * 
 * Almacena las marcas de los repuestos que se utilizan en el taller.
 */
class MarcaRepuesto extends ApiModel
{
    protected $table = 'marcas_repuestos';
    protected $primaryKey = 'id_marca_repuesto';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    public function repuestos()
    {
        return $this->hasMany(Repuesto::class, 'id_marca_repuesto', 'id_marca_repuesto');
    }
}

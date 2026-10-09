<?php

namespace App\Services;

use App\Models\ModeloVehiculo;
use Illuminate\Support\Facades\DB;

/**
 * Service ModeloVehiculoService
 * 
 * Gestiona el catlogo de modelos de vehiculos.
 */
class ModeloVehiculoService
{
    /**
     * Retorna el listado completo de modelos de vehiculos.
     * Consulta SQL Raw:
     * SELECT * FROM modelos_vehiculos;
     */
    public static function getAll()
    {
        return ModeloVehiculo::get();
    }

    /**
     * Obtiene un modelo por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM modelos_vehiculos WHERE id_modelo_vehiculo = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return ModeloVehiculo::find($id);
    }

    /**
     * Registra un nuevo modelo de vehiculo.
     * Consulta SQL Raw:
     * INSERT INTO modelos_vehiculos (nombre, id_marca_vehiculo) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $modelo = ModeloVehiculo::create($data);
        DB::commit();
        return $modelo;
    }

    /**
     * Modifica el nombre de una marca de vehiculo.
     * Consulta SQL Raw:
     * UPDATE marcas_vehiculos SET nombre = ... WHERE id_marca_vehiculo = $id;
     */
    public static function update($id, $data)
    {
        $modelo = ModeloVehiculo::find($id);
        if (!$modelo) {
            return null;
        }

        DB::beginTransaction();
        $modelo->update($data);
        DB::commit();
        return $modelo;
    }

    /**
     * Elimina un modelo de vehiculo de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM marcas_vehiculos WHERE id_marca_vehiculo = $id;
     */
    public static function delete($id)
    {
        $modelo = ModeloVehiculo::find($id);
        if (!$modelo) {
            return null;
        }

        DB::beginTransaction();
        $modelo->delete();
        DB::commit();
        return $modelo;
    }
}

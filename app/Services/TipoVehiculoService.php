<?php

namespace App\Services;

use App\Models\TipoVehiculo;
use Illuminate\Support\Facades\DB;

/**
 * Service TipoVehiculoService
 * 
 * Gestiona el catlogo de tipos de vehiculos.
 */
class TipoVehiculoService
{
    /**
     * Retorna el listado completo de tipos de vehiculos.
     * Consulta SQL Raw:
     * SELECT * FROM tipos_vehiculos;
     */
    public static function getAll()
    {
        return TipoVehiculo::get();
    }

    /**
     * Obtiene un tipo por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM tipos_vehiculos WHERE id_tipo_vehiculo = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return TipoVehiculo::find($id);
    }

    /**
     * Registra un nuevo tipo de vehiculo.
     * Consulta SQL Raw:
     * INSERT INTO tipos_vehiculos (nombre) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $tipo = TipoVehiculo::create($data);
        DB::commit();
        return $tipo;
    }

    /**
     * Modifica el nombre de un tipo de vehiculo.
     * Consulta SQL Raw:
     * UPDATE tipos_vehiculos SET nombre = ... WHERE id_tipo_vehiculo = $id;
     */
    public static function update($id, $data)
    {
        $tipo = TipoVehiculo::find($id);
        if (!$tipo) {
            return null;
        }

        DB::beginTransaction();
        $tipo->update($data);
        DB::commit();
        return $tipo;
    }

    /**
     * Elimina un tipo de vehiculo de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM tipos_vehiculos WHERE id_tipo_vehiculo = $id;
     */
    public static function delete($id)
    {
        $tipo = TipoVehiculo::find($id);
        if (!$tipo) {
            return null;
        }

        DB::beginTransaction();
        $tipo->delete();
        DB::commit();
        return $tipo;
    }
}

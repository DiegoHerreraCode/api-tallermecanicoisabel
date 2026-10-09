<?php

namespace App\Services;

use App\Models\MarcaVehiculo;
use Illuminate\Support\Facades\DB;

/**
 * Service MarcaVehiculoService
 * 
 * Gestiona el catlogo de marcas de vehiculos.
 */
class MarcaVehiculoService
{
    /**
     * Retorna el listado completo de marcas de vehiculos.
     * Consulta SQL Raw:
     * SELECT * FROM marcas_vehiculos;
     */
    public static function getAll()
    {
        return MarcaVehiculo::get();
    }

    /**
     * Obtiene una marca por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM marcas_vehiculos WHERE id_marca_vehiculo = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return MarcaVehiculo::find($id);
    }

    /**
     * Registra una nueva marca de vehiculo.
     * Consulta SQL Raw:
     * INSERT INTO marcas_vehiculos (nombre) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $marca = MarcaVehiculo::create($data);
        DB::commit();
        return $marca;
    }

    /**
     * Modifica el nombre de una marca de vehiculo.
     * Consulta SQL Raw:
     * UPDATE marcas_vehiculos SET nombre = ... WHERE id_marca_vehiculo = $id;
     */
    public static function update($id, $data)
    {
        $marca = MarcaVehiculo::find($id);
        if (!$marca) {
            return null;
        }

        DB::beginTransaction();
        $marca->update($data);
        DB::commit();
        return $marca;
    }

    /**
     * Elimina una marca de vehiculo de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM marcas_vehiculos WHERE id_marca_vehiculo = $id;
     */
    public static function delete($id)
    {
        $marca = MarcaVehiculo::find($id);
        if (!$marca) {
            return null;
        }

        DB::beginTransaction();
        $marca->delete();
        DB::commit();
        return $marca;
    }
}

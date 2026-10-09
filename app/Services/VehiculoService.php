<?php

namespace App\Services;

use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;

/**
 * Service VehiculoService
 * 
 * Gestiona el catálogo y la persistencia de vehiculos del sistema.
 */
class VehiculoService
{
    /**
     * Retorna todos los vehiculos registrados.
     * Consulta SQL Raw:
     * SELECT * FROM vehiculos;
     */
    public static function getAll()
    {
        return Vehiculo::get();
    }

    /**
     * Obtiene un vehiculo especfico por su ID.
     * Consulta SQL Raw:
     * SELECT * FROM vehiculos WHERE id_vehiculo = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return Vehiculo::find($id);
    }

    /**
     * Crea un nuevo vehiculo en base de datos.
     * Consulta SQL Raw:
     * INSERT INTO vehiculos (id_cliente, id_tipo_vehiculo, id_modelo_vehiculo, placa, annio) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $vehiculo = Vehiculo::create($data);
        DB::commit();
        return $vehiculo;
    }

    /**
     * Actualiza los datos de un vehiculo existente.
     * Consulta SQL Raw:
     * UPDATE vehiculos SET id_cliente = ..., id_tipo_vehiculo = ..., id_modelo_vehiculo = ..., placa = ..., annio = ... WHERE id_vehiculo = $id;
     */
    public static function update($id, $data)
    {
        $vehiculo = Vehiculo::find($id);
        if (!$vehiculo) {
            return null;
        }

        DB::beginTransaction();
        $vehiculo->update($data);
        DB::commit();
        return $vehiculo;
    }

    /**
     * Elimina el registro de un vehiculo.
     * Consulta SQL Raw:
     * DELETE FROM vehiculos WHERE id_vehiculo = $id;
     */
    public static function delete($id)
    {
        $vehiculo = Vehiculo::find($id);
        if (!$vehiculo) {
            return null;
        }

        DB::beginTransaction();
        $vehiculo->delete();
        DB::commit();
        return $vehiculo;
    }
}

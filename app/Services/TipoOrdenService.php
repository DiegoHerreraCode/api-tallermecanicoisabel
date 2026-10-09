<?php

namespace App\Services;

use App\Models\TipoOrden;
use Illuminate\Support\Facades\DB;

/**
 * Service TipoOrdenService
 * 
 * Gestiona el catlogo de tipos de orden de servicio que se manejan en el taller.
 */
class TipoOrdenService
{
    /**
     * Retorna el listado completo de tipos de orden de servicio.
     * Consulta SQL Raw:
     * SELECT * FROM tipos_ordenes;
     */
    public static function getAll()
    {
        return TipoOrden::get();
    }

    /**
     * Obtiene un tipo de orden por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM tipos_ordenes WHERE id_tipo_orden = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return TipoOrden::find($id);
    }

    /**
     * Registra un nuevo tipo de orden de servicio.
     * Consulta SQL Raw:
     * INSERT INTO tipos_ordenes (nombre, descripcion) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $tipoOrden = TipoOrden::create($data);
        DB::commit();
        return $tipoOrden;
    }

    /**
     * Modifica el nombre de un tipo de orden de servicio.
     * Consulta SQL Raw:
     * UPDATE tipos_ordenes SET nombre = ..., descripcion = ... WHERE id_tipo_orden = $id;
     */
    public static function update($id, $data)
    {
        $tipoOrden = TipoOrden::find($id);
        if (!$tipoOrden) {
            return null;
        }

        DB::beginTransaction();
        $tipoOrden->update($data);
        DB::commit();
        return $tipoOrden;
    }

    /**
     * Elimina un tipo de orden de servicio de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM tipos_ordenes WHERE id_tipo_orden = $id;
     */
    public static function delete($id)
    {
        $tipoOrden = TipoOrden::find($id);
        if (!$tipoOrden) {
            return null;
        }

        DB::beginTransaction();
        $tipoOrden->delete();
        DB::commit();
        return $tipoOrden;
    }
}

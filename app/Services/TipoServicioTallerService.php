<?php

namespace App\Services;

use App\Models\TipoServicioTaller;
use Illuminate\Support\Facades\DB;

/**
 * Service TipoServicioTallerService
 * 
 * Gestiona el catlogo de tipos de servicios que se realizan en el taller.
 */
class TipoServicioTallerService
{
    /**
     * Retorna el listado completo de tipos de servicios.
     * Consulta SQL Raw:
     * SELECT * FROM tipos_servicios_taller;
     */
    public static function getAll()
    {
        return TipoServicioTaller::get();
    }

    /**
     * Obtiene un tipo de servicio por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM tipos_servicios_taller WHERE id_tipo_servicio_taller = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return TipoServicioTaller::find($id);
    }

    /**
     * Registra un nuevo tipo de servicio.
     * Consulta SQL Raw:
     * INSERT INTO tipos_servicios_taller (nombre, descripcion) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $tipoServicioTaller = TipoServicioTaller::create($data);
        DB::commit();
        return $tipoServicioTaller;
    }

    /**
     * Modifica el nombre de un tipo de servicio.
     * Consulta SQL Raw:
     * UPDATE tipos_servicios_taller SET nombre = ..., descripcion = ... WHERE id_tipo_servicio_taller = $id;
     */
    public static function update($id, $data)
    {
        $tipoServicioTaller = TipoServicioTaller::find($id);
        if (!$tipoServicioTaller) {
            return null;
        }

        DB::beginTransaction();
        $tipoServicioTaller->update($data);
        DB::commit();
        return $tipoServicioTaller;
    }

    /**
     * Elimina un tipo de servicio de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM tipos_servicios_taller WHERE id_tipo_servicio_taller = $id;
     */
    public static function delete($id)
    {
        $tipoServicioTaller = TipoServicioTaller::find($id);
        if (!$tipoServicioTaller) {
            return null;
        }

        DB::beginTransaction();
        $tipoServicioTaller->delete();
        DB::commit();
        return $tipoServicioTaller;
    }
}

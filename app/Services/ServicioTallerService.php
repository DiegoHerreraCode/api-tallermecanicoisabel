<?php

namespace App\Services;

use App\Models\ServicioTaller;
use Illuminate\Support\Facades\DB;

/**
 * Service ServicioTallerService
 * 
 * Gestiona el catalogo de servicios de taller.
 */
class ServicioTallerService
{
    /**
     * Retorna el listado completo de servicios de taller.
     * Consulta SQL Raw:
     * SELECT * FROM servicios_taller;
     */
    public static function getAll()
    {
        return ServicioTaller::get();
    }

    /**
     * Obtiene un modelo por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM servicios_taller WHERE id_servicio_taller = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return ServicioTaller::find($id);
    }

    /**
     * Registra un nuevo servicio de taller.
     * Consulta SQL Raw:
     * INSERT INTO servicios_taller (id_tipo_servicio_taller, nombre, descripcion, costo, monto_venta, utilidad, porcentaje_ganancia, is_deleted) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $data['is_deleted'] = false;
        $servicioTaller = ServicioTaller::create($data);
        DB::commit();
        return $servicioTaller;
    }

    /**
     * Modifica un servicio de taller.
     * Consulta SQL Raw:
     * UPDATE servicios_taller SET id_tipo_servicio_taller = ..., nombre = ..., descripcion = ..., costo = ..., monto_venta = ..., utilidad = ..., porcentaje_ganancia = ..., is_deleted = ... WHERE id_servicio_taller = $id;
     */
    public static function update($id, $data)
    {
        $servicioTaller = ServicioTaller::find($id);
        if (!$servicioTaller) {
            return null;
        }

        DB::beginTransaction();
        $servicioTaller->update($data);
        DB::commit();
        return $servicioTaller;
    }

    /**
     * Elimina un servicio de taller de la base de datos.
     * Consulta SQL Raw:
     * UPDATE servicios_taller SET is_deleted = true WHERE id_servicio_taller = $id;
     */
    public static function delete($id)
    {
        $servicioTaller = ServicioTaller::where('id_servicio_taller', $id)->where('is_deleted', false)->first();
        if (!$servicioTaller) {
            return null;
        }

        // borrado logico
        DB::beginTransaction();
        $servicioTaller->update(['is_deleted' => true]);
        DB::commit();
        return $servicioTaller;
    }
}

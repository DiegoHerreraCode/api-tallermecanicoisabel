<?php

namespace App\Services;

use App\Models\PresupuestoServicioTaller;
use Illuminate\Support\Facades\DB;

/**
 * Service PresupuestoServicioTallerService
 * 
 * Gestiona los servicios asociados a los presupuestos.
 */
class PresupuestoServicioTallerService
{
    /**
     * Retorna todos los servicios asociados a presupuestos.
     */
    public static function getAll()
    {
        return PresupuestoServicioTaller::with(['servicioTaller', 'presupuesto'])->get();
    }

    /**
     * Obtiene un servicio de presupuesto por ID.
     */
    public static function getOne($id)
    {
        return PresupuestoServicioTaller::with(['servicioTaller', 'presupuesto'])->find($id);
    }

    /**
     * Obtiene todos los servicios de un presupuesto específico.
     */
    public static function getByPresupuesto($idPresupuesto)
    {
        return PresupuestoServicioTaller::with('servicioTaller')
            ->where('id_presupuesto', $idPresupuesto)
            ->get();
    }

    /**
     * Registra un servicio en un presupuesto.
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $registro = PresupuestoServicioTaller::create($data);
        DB::commit();
        return $registro;
    }

    /**
     * Actualiza un servicio en un presupuesto.
     */
    public static function update($id, $data)
    {
        $registro = PresupuestoServicioTaller::find($id);
        if (!$registro) {
            return null;
        }

        DB::beginTransaction();
        $registro->update($data);
        DB::commit();
        return $registro;
    }

    /**
     * Elimina un servicio de un presupuesto.
     */
    public static function delete($id)
    {
        $registro = PresupuestoServicioTaller::find($id);
        if (!$registro) {
            return null;
        }

        DB::beginTransaction();
        $registro->delete();
        DB::commit();
        return $registro;
    }
}

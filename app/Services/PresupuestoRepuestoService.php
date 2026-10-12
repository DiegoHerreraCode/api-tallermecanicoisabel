<?php

namespace App\Services;

use App\Models\PresupuestoRepuesto;
use Illuminate\Support\Facades\DB;

/**
 * Service PresupuestoRepuestoService
 * 
 * Gestiona los repuestos asociados a los presupuestos.
 */
class PresupuestoRepuestoService
{
    /**
     * Retorna todos los repuestos asociados a presupuestos.
     */
    public static function getAll()
    {
        return PresupuestoRepuesto::with(['repuesto', 'presupuesto'])->get();
    }

    /**
     * Obtiene un repuesto de presupuesto por ID.
     */
    public static function getOne($id)
    {
        return PresupuestoRepuesto::with(['repuesto', 'presupuesto'])->find($id);
    }

    /**
     * Obtiene todos los repuestos de un presupuesto específico.
     */
    public static function getByPresupuesto($idPresupuesto)
    {
        return PresupuestoRepuesto::with('repuesto')
            ->where('id_presupuesto', $idPresupuesto)
            ->get();
    }

    /**
     * Registra un repuesto en un presupuesto.
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $registro = PresupuestoRepuesto::create($data);
        DB::commit();
        return $registro;
    }

    /**
     * Actualiza un repuesto en un presupuesto.
     */
    public static function update($id, $data)
    {
        $registro = PresupuestoRepuesto::find($id);
        if (!$registro) {
            return null;
        }

        DB::beginTransaction();
        $registro->update($data);
        DB::commit();
        return $registro;
    }

    /**
     * Elimina un repuesto de un presupuesto.
     */
    public static function delete($id)
    {
        $registro = PresupuestoRepuesto::find($id);
        if (!$registro) {
            return null;
        }

        DB::beginTransaction();
        $registro->delete();
        DB::commit();
        return $registro;
    }
}

<?php

namespace App\Services;

use App\Models\OrdenRepuesto;
use Illuminate\Support\Facades\DB;

/**
 * Service OrdenRepuestoService
 * 
 * Gestiona los repuestos asociados a las órdenes de trabajo.
 */
class OrdenRepuestoService
{
    /**
     * Retorna todos los repuestos asociados a órdenes de trabajo.
     */
    public static function getAll()
    {
        return OrdenRepuesto::with(['repuesto', 'orden'])->get();
    }

    /**
     * Obtiene un repuesto de orden por ID.
     */
    public static function getOne($id)
    {
        return OrdenRepuesto::with(['repuesto', 'orden'])->find($id);
    }

    /**
     * Obtiene todos los repuestos de una orden específica.
     */
    public static function getByOrden($idOrden)
    {
        return OrdenRepuesto::with('repuesto')
            ->where('id_orden', $idOrden)
            ->get();
    }

    /**
     * Registra un repuesto en una orden.
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $registro = OrdenRepuesto::create($data);
        DB::commit();
        return $registro;
    }

    /**
     * Actualiza un registro de repuesto en una orden.
     */
    public static function update($id, $data)
    {
        $registro = OrdenRepuesto::find($id);
        if (!$registro) {
            return null;
        }

        DB::beginTransaction();
        $registro->update($data);
        DB::commit();
        return $registro;
    }

    /**
     * Elimina un repuesto de una orden.
     */
    public static function delete($id)
    {
        $registro = OrdenRepuesto::find($id);
        if (!$registro) {
            return null;
        }

        DB::beginTransaction();
        $registro->delete();
        DB::commit();
        return $registro;
    }
}

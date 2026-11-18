<?php

namespace App\Services;

use App\Models\HallazgoTecnico;
use App\Models\Orden;
use App\Models\Ingreso;
use Illuminate\Support\Facades\DB;

/**
 * Service HallazgoTecnicoService
 * 
 * Gestiona los hallazgos tecnicos realizados en taller asociados a una orden o a un ingreso.
 */
class HallazgoTecnicoService
{
    /**
     * Retorna el listado completo de hallazgos tecnicos (con opción de filtrar por orden o ingreso).
     */
    public static function getAll($filters = [])
    {
        $query = HallazgoTecnico::query();

        if (!empty($filters['id_orden'])) {
            $query->where('id_orden', $filters['id_orden']);
        }

        if (!empty($filters['id_ingreso'])) {
            $query->where('id_ingreso', $filters['id_ingreso']);
        }

        return $query->get();
    }

    /**
     * Obtiene un modelo por su clave primaria.
     */
    public static function getOne($id)
    {
        return HallazgoTecnico::find($id);
    }

    /**
     * Registra un nuevo hallazgo tecnico.
     */
    public static function create($data)
    {
        DB::beginTransaction();

        $hasOrden = !empty($data['id_orden']);
        $hasIngreso = !empty($data['id_ingreso']);

        if (!$hasOrden && !$hasIngreso) {
            DB::rollBack();
            return null;
        }

        // Si viene id_orden, validar que la orden exista y no esté finalizada ni anulada
        if ($hasOrden) {
            $orden = Orden::where('id_orden', $data['id_orden'])->first();
            if (!$orden || $orden->estado_operativo === 'finalizada' || $orden->estado_operativo === 'anulada') {
                DB::rollBack();
                return null;
            }
        }

        // Si viene id_ingreso, validar que el ingreso exista y no esté anulado
        if ($hasIngreso) {
            $ingreso = Ingreso::where('id_ingreso', $data['id_ingreso'])->first();
            if (!$ingreso || $ingreso->estado === 'anulado') {
                DB::rollBack();
                return null;
            }
        }

        $hallazgoTecnico = HallazgoTecnico::create($data);
        DB::commit();
        return $hallazgoTecnico;
    }

    /**
     * Modifica un hallazgo tecnico.
     */
    public static function update($id, $data)
    {
        $hallazgoTecnico = HallazgoTecnico::find($id);
        if (!$hallazgoTecnico) {
            return null;
        }

        // Validar orden asociada si existe
        if (!empty($hallazgoTecnico->id_orden)) {
            $orden = Orden::where('id_orden', $hallazgoTecnico->id_orden)->first();
            if (!$orden || $orden->estado_operativo === 'finalizada' || $orden->estado_operativo === 'anulada') {
                return null;
            }
        }

        // Validar ingreso asociado si existe
        if (!empty($hallazgoTecnico->id_ingreso)) {
            $ingreso = Ingreso::where('id_ingreso', $hallazgoTecnico->id_ingreso)->first();
            if (!$ingreso || $ingreso->estado === 'anulado') {
                return null;
            }
        }

        DB::beginTransaction();
        $hallazgoTecnico->update($data);
        DB::commit();
        return $hallazgoTecnico;
    }

    /**
     * Elimina un hallazgo tecnico de la base de datos.
     */
    public static function delete($id)
    {
        $hallazgoTecnico = HallazgoTecnico::find($id);
        if (!$hallazgoTecnico) {
            return null;
        }

        // Validar orden asociada si existe
        if (!empty($hallazgoTecnico->id_orden)) {
            $orden = Orden::where('id_orden', $hallazgoTecnico->id_orden)->first();
            if (!$orden || $orden->estado_operativo === 'finalizada' || $orden->estado_operativo === 'anulada') {
                return null;
            }
        }

        // Validar ingreso asociado si existe
        if (!empty($hallazgoTecnico->id_ingreso)) {
            $ingreso = Ingreso::where('id_ingreso', $hallazgoTecnico->id_ingreso)->first();
            if (!$ingreso || $ingreso->estado === 'anulado') {
                return null;
            }
        }

        DB::beginTransaction();
        $hallazgoTecnico->delete();
        DB::commit();
        return $hallazgoTecnico;
    }
}

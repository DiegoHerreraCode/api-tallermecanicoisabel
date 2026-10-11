<?php

namespace App\Services;

use App\Models\HallazgoTecnico;
use App\Models\Orden;
use Illuminate\Support\Facades\DB;

/**
 * Service HallazgoTecnicoService
 * 
 * Gestiona los hallazgos tecnicos realizados en taller.
 */
class HallazgoTecnicoService
{
    /**
     * Retorna el listado completo de hallazgos tecnicos.
     * Consulta SQL Raw:
     * SELECT * FROM hallazgos_tecnicos;
     */
    public static function getAll()
    {
        return HallazgoTecnico::get();
    }

    /**
     * Obtiene un modelo por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM hallazgos_tecnicos WHERE id_hallazgo_tecnico = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return HallazgoTecnico::find($id);
    }

    /**
     * Registra un nuevo hallazgo tecnico.
     * Consulta SQL Raw:
     * INSERT INTO hallazgos_tecnicos (id_orden, hallazgo, detalle) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        // antes de crear validar que el id_orden exista y no este asociado a una orden con estado operativo = 'finalizada' o 'anulada'
        $orden = Orden::where('id_orden', $data['id_orden'])->first();
        if (!$orden) {
            return null;
        }
        if ($orden->estado_operativo == 'finalizada' || $orden->estado_operativo == 'anulada') {
            return null;
        }
        $hallazgoTecnico = HallazgoTecnico::create($data);
        DB::commit();
        return $hallazgoTecnico;
    }

    /**
     * Modifica un hallazgo tecnico.
     * Consulta SQL Raw:
     * UPDATE hallazgos_tecnicos SET id_orden = ..., hallazgo = ..., detalle = ... WHERE id_hallazgo_tecnico = $id;
     */
    public static function update($id, $data)
    {
        $hallazgoTecnico = HallazgoTecnico::find($id);
        if (!$hallazgoTecnico) {
            return null;
        }
        // antes de actualizar validar que el id_orden exista y no este asociado a una orden con estado operativo = 'finalizada' o 'anulada'
        $orden = Orden::where('id_orden', $hallazgoTecnico->id_orden)->first();
        if (!$orden) {
            return null;
        }
        if ($orden->estado_operativo == 'finalizada' || $orden->estado_operativo == 'anulada') {
            return null;
        }
        DB::beginTransaction();
        $hallazgoTecnico->update($data);
        DB::commit();
        return $hallazgoTecnico;
    }

    /**
     * Elimina un hallazgo tecnico de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM hallazgos_tecnicos WHERE id_hallazgo_tecnico = $id;
     */
    public static function delete($id)
    {
        $hallazgoTecnico = HallazgoTecnico::find($id);
        if (!$hallazgoTecnico) {
            return null;
        }
        // antes de eliminar validar que el id_orden exista y no este asociado a una orden con estado operativo = 'finalizada' o 'anulada'
        $orden = Orden::where('id_orden', $hallazgoTecnico->id_orden)->first();
        if (!$orden) {
            return null;
        }
        if ($orden->estado_operativo == 'finalizada' || $orden->estado_operativo == 'anulada') {
            return null;
        }

        DB::beginTransaction();
        $hallazgoTecnico->delete();
        DB::commit();
        return $hallazgoTecnico;
    }
}

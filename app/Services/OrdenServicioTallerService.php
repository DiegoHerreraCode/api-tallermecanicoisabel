<?php

namespace App\Services;

use App\Models\OrdenServicioTaller;
use App\Models\Orden;
use App\Enums\OrdenServicioTallerEstado;
use App\Enums\OrdenEstadoOperativo;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service OrdenServicioTallerService
 * 
 * Gestiona los servicios de taller asociados a las órdenes de trabajo.
 */
class OrdenServicioTallerService
{
    /**
     * Retorna todos los servicios asociados a órdenes de trabajo.
     */
    public static function getAll()
    {
        return OrdenServicioTaller::with(['servicioTaller', 'personal', 'orden'])->get();
    }

    /**
     * Obtiene un servicio de orden por ID.
     */
    public static function getOne($id)
    {
        return OrdenServicioTaller::with(['servicioTaller', 'personal', 'orden'])->find($id);
    }

    /**
     * Obtiene todos los servicios de una orden específica.
     */
    public static function getByOrden($idOrden)
    {
        return OrdenServicioTaller::with(['servicioTaller', 'personal'])
            ->where('id_orden', $idOrden)
            ->get();
    }

    /**
     * Registra un servicio en una orden.
     */
    public static function create($data)
    {
        DB::beginTransaction();
        if (empty($data['estado'])) {
            $data['estado'] = !empty($data['id_personal'])
                ? OrdenServicioTallerEstado::EN_ESPERA
                : OrdenServicioTallerEstado::PENDIENTE_ASIGNACION;
        }
        $registro = OrdenServicioTaller::create($data);
        DB::commit();
        return $registro;
    }

    /**
     * Actualiza un servicio en una orden.
     * Si el servicio pasa a 'en_proceso', la orden asociada cambia de 'en_espera' a 'en_proceso'.
     * Si se asigna id_personal y el estado estaba en 'pendiente_asignacion', pasa a 'en_espera'.
     */
    public static function update($id, $data)
    {
        $registro = OrdenServicioTaller::find($id);
        if (!$registro) {
            return null;
        }

        DB::beginTransaction();

        // Si se asigna personal y el estado es pendiente_asignacion y no se especificó otro estado
        if (!empty($data['id_personal']) && empty($data['estado']) && $registro->estado === OrdenServicioTallerEstado::PENDIENTE_ASIGNACION->value) {
            $data['estado'] = OrdenServicioTallerEstado::EN_ESPERA->value;
        }

        // Si el estado pasa a en_proceso y no tiene fecha_inicio asignada
        if (isset($data['estado']) && $data['estado'] === OrdenServicioTallerEstado::EN_PROCESO->value) {
            if (empty($data['fecha_inicio']) && empty($registro->fecha_inicio)) {
                $data['fecha_inicio'] = now();
            }
        }

        // Si el estado pasa a finalizado y no tiene fecha_fin asignada
        if (isset($data['estado']) && $data['estado'] === OrdenServicioTallerEstado::FINALIZADO->value) {
            if (empty($data['fecha_fin']) && empty($registro->fecha_fin)) {
                $data['fecha_fin'] = now();
            }
        }

        $registro->update($data);

        // Si el servicio pasó a en_proceso, actualizar la orden de 'en_espera' a 'en_proceso'
        if ($registro->estado === OrdenServicioTallerEstado::EN_PROCESO->value) {
            $orden = Orden::find($registro->id_orden);
            if ($orden && $orden->estado_operativo === OrdenEstadoOperativo::EN_ESPERA->value) {
                $orden->update([
                    'estado_operativo' => OrdenEstadoOperativo::EN_PROCESO,
                    'last_update' => now(),
                ]);
            }
        }

        DB::commit();
        return self::getOne($id);
    }

    /**
     * Elimina un servicio de una orden.
     * Solo permite eliminar si su estado es 'pendiente_asignacion' o 'en_espera'.
     * Lanza una Exception si el estado no lo permite.
     */
    public static function delete($id)
    {
        $registro = OrdenServicioTaller::find($id);
        if (!$registro) {
            return null;
        }

        $estadosPermitidos = [
            OrdenServicioTallerEstado::PENDIENTE_ASIGNACION->value,
            OrdenServicioTallerEstado::EN_ESPERA->value,
        ];

        if (!in_array($registro->estado, $estadosPermitidos, true)) {
            throw new Exception("No se puede eliminar el servicio porque su estado actual es '{$registro->estado}'. Solo se permite eliminar servicios en estado 'pendiente_asignacion' o 'en_espera'.");
        }

        DB::beginTransaction();
        $registro->delete();
        DB::commit();
        return $registro;
    }
}

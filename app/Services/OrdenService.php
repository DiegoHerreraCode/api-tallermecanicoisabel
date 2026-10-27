<?php

namespace App\Services;

use App\Models\Orden;
use App\Models\OrdenRepuesto;
use App\Models\OrdenServicioTaller;
use App\Models\Admin;
use App\Models\Ingreso;
use App\Models\Presupuesto;
use App\Enums\PresupuestoTipo;
use App\Enums\PresupuestoEstado;
use App\Enums\OrdenEstadoOperativo;
use App\Enums\OrdenEstadoAdministrativo;
use App\Enums\OrdenServicioTallerEstado;
use App\Enums\IngresoEstado;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service OrdenService
 * 
 * Gestiona el catálogo de órdenes de trabajo en el taller y sus detalles asociados.
 */
class OrdenService
{
    /**
     * Retorna el listado completo de órdenes de trabajo con sus repuestos y servicios.
     */
    public static function getAll()
    {
        return Orden::with(['repuestos.repuesto', 'serviciosTaller.servicioTaller', 'serviciosTaller.personal', 'tipoOrden'])->get();
    }

    /**
     * Obtiene una orden por su clave primaria con detalles.
     */
    public static function getOne($id)
    {
        return Orden::with(['repuestos.repuesto', 'serviciosTaller.servicioTaller', 'serviciosTaller.personal'])->find($id);
    }

    /**
     * Registra una nueva orden de trabajo con sus detalles de repuestos y servicios.
     */
    public static function create($data)
    {
        $ingreso = Ingreso::find($data['id_ingreso'] ?? null);
        if (!$ingreso) {
            throw new Exception("El ingreso especificado no existe.");
        }

        if ($ingreso->estado === IngresoEstado::CONFIRMANDO_PRESUPUESTO->value) {
            throw new Exception("No se puede crear la orden directamente porque el ingreso está en estado 'confirmando_presupuesto'. Primero debe resolverse el presupuesto.");
        }

        if ($ingreso->estado !== IngresoEstado::ESPERANDO_ORDEN->value) {
            throw new Exception("Solo se puede crear una orden para ingresos en estado 'esperando_orden'. El estado actual es '{$ingreso->estado}'.");
        }

        DB::beginTransaction();

        $array_repuestos = $data['repuestos'] ?? [];
        $array_servicios_taller = $data['servicios_taller'] ?? [];

        unset($data['repuestos']);
        unset($data['servicios_taller']);

        if (empty($data['id_admin'])) {
            $userId = auth()->id();
            $admin = $userId ? Admin::where('id_user', $userId)->first() : null;
            $data['id_admin'] = $admin ? $admin->id_admin : 1;
        }

        $data['fecha_creacion'] = now();
        $data['estado_operativo'] = OrdenEstadoOperativo::EN_ESPERA;
        $data['estado_administrativo'] = OrdenEstadoAdministrativo::PENDIENTE_PAGO;
        $data['last_update'] = now();
        $data['monto_pendiente'] = $data['monto_total'];

        $orden = Orden::create($data);

        // Guardar repuestos seleccionados
        foreach ($array_repuestos as $repuesto) {
            OrdenRepuesto::create([
                'id_orden' => $orden->id_orden,
                'id_repuesto' => $repuesto['id_repuesto'],
                'costo_unitario_base' => $repuesto['costo_unitario_base'] ?? null,
                'precio_unitario' => $repuesto['precio_unitario'],
                'porcentaje_ganancia' => $repuesto['porcentaje_ganancia'] ?? null,
                'cantidad' => $repuesto['cantidad'],
                'monto_total_linea_base' => $repuesto['monto_total_linea_base'] ?? null,
                'monto_total_linea' => $repuesto['monto_total_linea'],
            ]);
        }

        // Guardar servicios seleccionados
        foreach ($array_servicios_taller as $servicioTaller) {
            $idPersonal = $servicioTaller['id_personal'] ?? null;
            $estadoServicio = !empty($idPersonal)
                ? OrdenServicioTallerEstado::EN_ESPERA
                : OrdenServicioTallerEstado::PENDIENTE_ASIGNACION;

            OrdenServicioTaller::create([
                'id_orden' => $orden->id_orden,
                'id_servicio_taller' => $servicioTaller['id_servicio_taller'],
                'id_personal' => $idPersonal,
                'costo_unitario_base' => $servicioTaller['costo_unitario_base'] ?? null,
                'precio_unitario' => $servicioTaller['precio_unitario'],
                'porcentaje_ganancia' => $servicioTaller['porcentaje_ganancia'] ?? null,
                'cantidad' => $servicioTaller['cantidad'],
                'monto_total_linea_base' => $servicioTaller['monto_total_linea_base'] ?? null,
                'monto_total_linea' => $servicioTaller['monto_total_linea'],
                'estado' => $servicioTaller['estado'] ?? $estadoServicio,
                'fecha_inicio' => $servicioTaller['fecha_inicio'] ?? null,
                'fecha_fin' => $servicioTaller['fecha_fin'] ?? null,
            ]);
        }

        // Actualizar el estado del ingreso a orden_creada
        $ingreso = Ingreso::find($orden->id_ingreso);
        if ($ingreso) {
            $ingreso->update([
                'estado' => IngresoEstado::ORDEN_CREADA,
                'last_update' => now(),
            ]);
        }

        DB::commit();

        return self::getOne($orden->id_orden);
    }

    /**
     * Modifica una orden y sincroniza repuestos y servicios.
     * Si se intenta cambiar el estado de la orden a 'finalizada', valida que todos sus servicios estén en 'finalizado'.
     * Si se intenta sincronizar y eliminar un servicio cuyo estado es distinto a 'pendiente_asignacion' o 'en_espera', lanza Exception.
     */
    public static function update($id, $data)
    {
        $orden = Orden::find($id);
        if (!$orden) {
            return null;
        }

        // Validar que la orden no tenga un presupuesto extra en estado pendiente
        $extraPendiente = Presupuesto::where('id_orden', $orden->id_orden)
            ->where('tipo', PresupuestoTipo::EXTRA->value)
            ->where('estado', PresupuestoEstado::PENDIENTE->value)
            ->first();

        if ($extraPendiente) {
            throw new Exception("No se puede modificar la orden directamente porque tiene el presupuesto extra {$extraPendiente->num_presupuesto} en estado 'pendiente' de aprobación por el cliente.");
        }

        // Validación: si se desea marcar la orden como finalizada, todos sus servicios deben estar finalizados y no debe tener presupuestos extras pendientes
        if (isset($data['estado_operativo']) && $data['estado_operativo'] === OrdenEstadoOperativo::FINALIZADA->value) {
            $extraPendienteFinalizar = Presupuesto::where('id_orden', $orden->id_orden)
                ->where('tipo', PresupuestoTipo::EXTRA->value)
                ->where('estado', PresupuestoEstado::PENDIENTE->value)
                ->first();

            if ($extraPendienteFinalizar) {
                throw new Exception("No se puede marcar la orden como finalizada porque tiene el presupuesto extra {$extraPendienteFinalizar->num_presupuesto} en estado 'pendiente' de aprobación por el cliente.");
            }

            $serviciosNoFinalizados = OrdenServicioTaller::where('id_orden', $orden->id_orden)
                ->where('estado', '!=', OrdenServicioTallerEstado::FINALIZADO->value)
                ->count();

            if ($serviciosNoFinalizados > 0) {
                throw new Exception("No se puede marcar la orden como finalizada porque tiene {$serviciosNoFinalizados} servicio(s) sin finalizar.");
            }

            if (empty($data['fecha_fin']) && empty($orden->fecha_fin)) {
                $data['fecha_fin'] = now();
            }
        }

        DB::beginTransaction();

        $hasRepuestos = array_key_exists('repuestos', $data);
        $hasServicios = array_key_exists('servicios_taller', $data);

        $array_repuestos = $data['repuestos'] ?? [];
        $array_servicios_taller = $data['servicios_taller'] ?? [];

        unset($data['repuestos']);
        unset($data['servicios_taller']);

        $data['last_update'] = now();

        if (isset($data['monto_total'])) {
            $data['monto_pendiente'] = $data['monto_total'];
        }

        $orden->update($data);

        // Sincronización de repuestos
        if ($hasRepuestos) {
            $payloadRepuestoIds = array_column($array_repuestos, 'id_repuesto');

            OrdenRepuesto::where('id_orden', $orden->id_orden)
                ->whereNotIn('id_repuesto', $payloadRepuestoIds)
                ->delete();

            foreach ($array_repuestos as $repuestoItem) {
                $existente = OrdenRepuesto::where('id_orden', $orden->id_orden)
                    ->where('id_repuesto', $repuestoItem['id_repuesto'])
                    ->first();

                $datosRepuesto = [
                    'costo_unitario_base' => $repuestoItem['costo_unitario_base'] ?? ($existente ? $existente->costo_unitario_base : null),
                    'precio_unitario' => $repuestoItem['precio_unitario'],
                    'porcentaje_ganancia' => $repuestoItem['porcentaje_ganancia'] ?? ($existente ? $existente->porcentaje_ganancia : null),
                    'cantidad' => $repuestoItem['cantidad'],
                    'monto_total_linea_base' => $repuestoItem['monto_total_linea_base'] ?? ($existente ? $existente->monto_total_linea_base : null),
                    'monto_total_linea' => $repuestoItem['monto_total_linea'],
                ];

                if ($existente) {
                    $existente->update($datosRepuesto);
                } else {
                    $datosRepuesto['id_orden'] = $orden->id_orden;
                    $datosRepuesto['id_repuesto'] = $repuestoItem['id_repuesto'];
                    OrdenRepuesto::create($datosRepuesto);
                }
            }
        }

        // Sincronización de servicios de taller
        if ($hasServicios) {
            $payloadServicioIds = array_column($array_servicios_taller, 'id_servicio_taller');

            // Validar que los servicios a eliminar sólo estén en 'pendiente_asignacion' o 'en_espera'
            $serviciosAEliminar = OrdenServicioTaller::where('id_orden', $orden->id_orden)
                ->whereNotIn('id_servicio_taller', $payloadServicioIds)
                ->get();

            $estadosPermitidos = [
                OrdenServicioTallerEstado::PENDIENTE_ASIGNACION->value,
                OrdenServicioTallerEstado::EN_ESPERA->value,
            ];

            foreach ($serviciosAEliminar as $srvEliminar) {
                if (!in_array($srvEliminar->estado, $estadosPermitidos, true)) {
                    throw new Exception("No se puede eliminar el servicio ID {$srvEliminar->id_servicio_taller} de la orden porque su estado es '{$srvEliminar->estado}'.");
                }
            }

            // Proceder a eliminar los permitidos
            OrdenServicioTaller::where('id_orden', $orden->id_orden)
                ->whereNotIn('id_servicio_taller', $payloadServicioIds)
                ->delete();

            // Actualizar o crear
            foreach ($array_servicios_taller as $servicioItem) {
                $existente = OrdenServicioTaller::where('id_orden', $orden->id_orden)
                    ->where('id_servicio_taller', $servicioItem['id_servicio_taller'])
                    ->first();

                $idPersonal = $servicioItem['id_personal'] ?? ($existente ? $existente->id_personal : null);

                $updateData = [
                    'costo_unitario_base' => $servicioItem['costo_unitario_base'] ?? ($existente ? $existente->costo_unitario_base : null),
                    'precio_unitario' => $servicioItem['precio_unitario'],
                    'porcentaje_ganancia' => $servicioItem['porcentaje_ganancia'] ?? ($existente ? $existente->porcentaje_ganancia : null),
                    'cantidad' => $servicioItem['cantidad'],
                    'monto_total_linea_base' => $servicioItem['monto_total_linea_base'] ?? ($existente ? $existente->monto_total_linea_base : null),
                    'monto_total_linea' => $servicioItem['monto_total_linea'],
                    'id_personal' => $idPersonal,
                ];

                if (isset($servicioItem['estado'])) {
                    $updateData['estado'] = $servicioItem['estado'];
                }
                if (array_key_exists('fecha_inicio', $servicioItem)) {
                    $updateData['fecha_inicio'] = $servicioItem['fecha_inicio'];
                }
                if (array_key_exists('fecha_fin', $servicioItem)) {
                    $updateData['fecha_fin'] = $servicioItem['fecha_fin'];
                }

                if ($existente) {
                    $existente->update($updateData);
                } else {
                    if (empty($updateData['estado'])) {
                        $updateData['estado'] = !empty($idPersonal)
                            ? OrdenServicioTallerEstado::EN_ESPERA
                            : OrdenServicioTallerEstado::PENDIENTE_ASIGNACION;
                    }
                    $updateData['id_orden'] = $orden->id_orden;
                    $updateData['id_servicio_taller'] = $servicioItem['id_servicio_taller'];
                    OrdenServicioTaller::create($updateData);
                }
            }
        }

        DB::commit();

        return self::getOne($orden->id_orden);
    }

    /**
     * Anula una orden de trabajo y pasa todos sus servicios al estado 'anulado'.
     */
    public static function anular($id, $motivoAnulacion, $idAdminAnulacion = null)
    {
        $orden = Orden::find($id);
        if (!$orden) {
            return null;
        }

        // Validar que la orden no tenga un presupuesto extra en estado pendiente
        $extraPendiente = Presupuesto::where('id_orden', $orden->id_orden)
            ->where('tipo', PresupuestoTipo::EXTRA->value)
            ->where('estado', PresupuestoEstado::PENDIENTE->value)
            ->first();

        if ($extraPendiente) {
            throw new Exception("No se puede anular la orden porque tiene el presupuesto extra {$extraPendiente->num_presupuesto} en estado 'pendiente' de aprobación por el cliente.");
        }

        if ($orden->estado_operativo === OrdenEstadoOperativo::ANULADA->value) {
            throw new Exception("No se puede anular la orden porque ya tiene el estado '{$orden->estado_operativo}'.");
        }

        if ($orden->estado_administrativo !== OrdenEstadoAdministrativo::PENDIENTE_PAGO->value) {
            throw new Exception("No se puede anular la orden porque ya tiene pagos asociados (estado '{$orden->estado_administrativo}').");
        }

        DB::beginTransaction();

        // 1. Actualizar la cabecera de la orden
        $orden->update([
            'estado_operativo' => OrdenEstadoOperativo::ANULADA,
            'fecha_anulacion' => now(),
            'id_admin_anulacion' => $idAdminAnulacion ?: 1,
            'motivo_anulacion' => $motivoAnulacion,
            'last_update' => now(),
        ]);

        // 2. Anular todos los servicios asociados a la orden
        OrdenServicioTaller::where('id_orden', $orden->id_orden)->update([
            'estado' => OrdenServicioTallerEstado::ANULADO,
        ]);

        DB::commit();

        return self::getOne($orden->id_orden);
    }

    /**
     * Elimina una orden y sus detalles asociados de la base de datos.
     */
    public static function delete($id)
    {
        $orden = Orden::find($id);
        if (!$orden) {
            return null;
        }

        DB::beginTransaction();
        OrdenRepuesto::where('id_orden', $id)->delete();
        OrdenServicioTaller::where('id_orden', $id)->delete();
        $orden->delete();
        DB::commit();

        return $orden;
    }
}

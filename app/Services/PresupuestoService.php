<?php

namespace App\Services;

use App\Models\Presupuesto;
use App\Models\PresupuestoRepuesto;
use App\Models\PresupuestoServicioTaller;
use App\Models\Ingreso;
use App\Models\Orden;
use App\Models\OrdenRepuesto;
use App\Models\OrdenServicioTaller;
use App\Models\Admin;
use App\Enums\PresupuestoTipo;
use App\Enums\PresupuestoEstado;
use App\Enums\IngresoEstado;
use App\Enums\OrdenEstadoOperativo;
use App\Enums\OrdenEstadoAdministrativo;
use App\Enums\OrdenServicioTallerEstado;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service PresupuestoService
 * 
 * Gestiona el ciclo de vida de los presupuestos y su conversión en órdenes de trabajo.
 */
class PresupuestoService
{
    /**
     * Retorna el listado completo de presupuestos con sus detalles.
     */
    public static function getAll()
    {
        return Presupuesto::with([
            'repuestos.repuesto',
            'serviciosTaller.servicioTaller',
            'ingreso',
            'orden',
            'admin'
        ])->get();
    }

    /**
     * Obtiene un presupuesto por su clave primaria con detalles.
     */
    public static function getOne($id)
    {
        return Presupuesto::with([
            'repuestos.repuesto',
            'serviciosTaller.servicioTaller',
            'ingreso',
            'orden',
            'admin'
        ])->find($id);
    }

    /**
     * Registra un nuevo presupuesto inicial.
     * Solo se permite si el ingreso está en estado 'esperando_orden'.
     * Al crearse, el ingreso pasa a 'confirmando_presupuesto'.
     */
    public static function create($data)
    {
        $idIngreso = $data['id_ingreso'] ?? null;
        $ingreso = Ingreso::find($idIngreso);

        if (!$ingreso) {
            throw new Exception("El ingreso especificado no existe.");
        }

        if ($ingreso->estado === IngresoEstado::CONFIRMANDO_PRESUPUESTO->value) {
            throw new Exception("El ingreso ya tiene un presupuesto en proceso de confirmación. No se pueden generar más presupuestos simultáneos.");
        }

        if ($ingreso->estado !== IngresoEstado::ESPERANDO_ORDEN->value) {
            throw new Exception("Solo se pueden generar presupuestos iniciales para ingresos en estado 'esperando_orden'. El estado actual es '{$ingreso->estado}'.");
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

        $now = now();
        $data['tipo'] = PresupuestoTipo::INICIAL;
        $data['estado'] = PresupuestoEstado::PENDIENTE;
        $data['fecha_creacion'] = $now;
        $data['fecha_vencimiento'] = (clone $now)->addDay();
        $data['num_presupuesto'] = 'PRE-TEMP'; // Temporal para cumplir con not null antes de generar ID
        $data['pdf_presupuesto'] = $data['pdf_presupuesto'] ?? 'pdf-presupuesto-temp';

        $presupuesto = Presupuesto::create($data);

        // Formatear num_presupuesto con PRE-0000X
        $formattedNum = 'PRE-' . str_pad($presupuesto->id_presupuesto, 5, '0', STR_PAD_LEFT);
        $presupuesto->update([
            'num_presupuesto' => $formattedNum,
            'pdf_presupuesto' => "pdf-presupuesto-{$presupuesto->id_presupuesto}",
        ]);

        // Guardar repuestos del presupuesto
        foreach ($array_repuestos as $repuesto) {
            PresupuestoRepuesto::create([
                'id_presupuesto' => $presupuesto->id_presupuesto,
                'id_repuesto' => $repuesto['id_repuesto'],
                'precio_unitario' => $repuesto['precio_unitario'],
                'cantidad' => $repuesto['cantidad'],
                'monto_total_linea' => $repuesto['monto_total_linea'],
            ]);
        }

        // Guardar servicios del presupuesto
        foreach ($array_servicios_taller as $servicioTaller) {
            PresupuestoServicioTaller::create([
                'id_presupuesto' => $presupuesto->id_presupuesto,
                'id_servicio_taller' => $servicioTaller['id_servicio_taller'],
                'precio_unitario' => $servicioTaller['precio_unitario'],
                'cantidad' => $servicioTaller['cantidad'],
                'monto_total_linea' => $servicioTaller['monto_total_linea'],
            ]);
        }

        // Actualizar el estado del ingreso a confirmando_presupuesto
        $ingreso->update([
            'estado' => IngresoEstado::CONFIRMANDO_PRESUPUESTO,
            'last_update' => now(),
        ]);

        DB::commit();

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Acepta un presupuesto inicial y genera la orden de trabajo correspondiente.
     * Vincula el id_orden en el presupuesto, cambia el estado del presupuesto a 'aceptado'
     * y el estado del ingreso a 'orden_creada'.
     */
    public static function aceptar($id, $data)
    {
        $presupuesto = Presupuesto::with(['repuestos', 'serviciosTaller', 'ingreso.vehiculo'])->find($id);

        if (!$presupuesto) {
            return null;
        }

        if ($presupuesto->estado !== PresupuestoEstado::PENDIENTE->value) {
            throw new Exception("Solo se pueden aceptar presupuestos en estado 'pendiente'. El estado actual es '{$presupuesto->estado}'.");
        }

        $ingreso = $presupuesto->ingreso;
        if (!$ingreso) {
            throw new Exception("El ingreso asociado a este presupuesto no fue encontrado.");
        }

        $idCliente = $ingreso->vehiculo ? $ingreso->vehiculo->id_cliente : $data['id_cliente'] ?? null;
        if (!$idCliente) {
            throw new Exception("No se pudo determinar el cliente asociado al ingreso del vehículo.");
        }

        DB::beginTransaction();

        // 1. Crear la Orden de Trabajo
        $orden = Orden::create([
            'id_ingreso' => $ingreso->id_ingreso,
            'id_tipo_orden' => $ingreso->id_tipo_orden,
            'id_cliente' => $idCliente,
            'fecha_creacion' => now(),
            'fecha_fin_tentativa' => $data['fecha_fin_tentativa'],
            'estado_operativo' => OrdenEstadoOperativo::EN_ESPERA,
            'estado_administrativo' => OrdenEstadoAdministrativo::PENDIENTE_PAGO,
            'monto_subtotal' => $presupuesto->monto_subtotal,
            'porcentaje_iva' => $presupuesto->porcentaje_iva,
            'monto_iva' => $presupuesto->monto_iva,
            'monto_total' => $presupuesto->monto_total,
            'monto_pendiente' => $presupuesto->monto_total,
            'last_update' => now(),
        ]);

        // 2. Grabar los repuestos en la orden
        foreach ($presupuesto->repuestos as $repuesto) {
            OrdenRepuesto::create([
                'id_orden' => $orden->id_orden,
                'id_repuesto' => $repuesto->id_repuesto,
                'precio_unitario' => $repuesto->precio_unitario,
                'cantidad' => $repuesto->cantidad,
                'monto_total_linea' => $repuesto->monto_total_linea,
            ]);
        }

        // 3. Grabar los servicios en la orden con estado pendiente_asignacion
        foreach ($presupuesto->serviciosTaller as $servicioTaller) {
            OrdenServicioTaller::create([
                'id_orden' => $orden->id_orden,
                'id_servicio_taller' => $servicioTaller->id_servicio_taller,
                'id_personal' => null,
                'precio_unitario' => $servicioTaller->precio_unitario,
                'cantidad' => $servicioTaller->cantidad,
                'monto_total_linea' => $servicioTaller->monto_total_linea,
                'estado' => OrdenServicioTallerEstado::PENDIENTE_ASIGNACION,
                'fecha_inicio' => null,
                'fecha_fin' => null,
            ]);
        }

        // 4. Actualizar el presupuesto: id_orden y estado = aceptado
        $presupuesto->update([
            'id_orden' => $orden->id_orden,
            'estado' => PresupuestoEstado::ACEPTADO,
        ]);

        // 5. Actualizar el ingreso a orden_creada
        $ingreso->update([
            'estado' => IngresoEstado::ORDEN_CREADA,
            'last_update' => now(),
        ]);

        DB::commit();

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Rechaza un presupuesto inicial.
     * Pasa el presupuesto a 'rechazado' y el ingreso regresa a 'esperando_orden'.
     */
    public static function rechazar($id)
    {
        $presupuesto = Presupuesto::with('ingreso')->find($id);

        if (!$presupuesto) {
            return null;
        }

        if ($presupuesto->estado !== PresupuestoEstado::PENDIENTE->value) {
            throw new Exception("Solo se pueden rechazar presupuestos en estado 'pendiente'. El estado actual es '{$presupuesto->estado}'.");
        }

        DB::beginTransaction();

        $presupuesto->update([
            'estado' => PresupuestoEstado::RECHAZADO,
        ]);

        if ($presupuesto->ingreso) {
            $presupuesto->ingreso->update([
                'estado' => IngresoEstado::ESPERANDO_ORDEN,
                'last_update' => now(),
            ]);
        }

        DB::commit();

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Anula un presupuesto.
     * Pasa el presupuesto a 'anulado' con motivo y admin de anulación,
     * y el ingreso regresa a 'esperando_orden'.
     */
    public static function anular($id, $motivoAnulacion, $idAdminAnulacion = null)
    {
        $presupuesto = Presupuesto::with('ingreso')->find($id);

        if (!$presupuesto) {
            return null;
        }

        if ($presupuesto->estado !== PresupuestoEstado::PENDIENTE->value) {
            throw new Exception("Solo se pueden anular presupuestos en estado 'pendiente'. El estado actual es '{$presupuesto->estado}'.");
        }

        DB::beginTransaction();

        $presupuesto->update([
            'estado' => PresupuestoEstado::ANULADO,
            'fecha_anulacion' => now(),
            'id_admin_anulacion' => $idAdminAnulacion ?: 1,
            'motivo_anulacion' => $motivoAnulacion,
        ]);

        // Si el ingreso estaba en confirmando_presupuesto por este presupuesto, regresa a esperando_orden
        if ($presupuesto->ingreso && $presupuesto->ingreso->estado === IngresoEstado::CONFIRMANDO_PRESUPUESTO->value) {
            $presupuesto->ingreso->update([
                'estado' => IngresoEstado::ESPERANDO_ORDEN,
                'last_update' => now(),
            ]);
        }

        DB::commit();

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Elimina un presupuesto y sus detalles.
     */
    public static function delete($id)
    {
        $presupuesto = Presupuesto::find($id);
        if (!$presupuesto) {
            return null;
        }

        DB::beginTransaction();
        PresupuestoRepuesto::where('id_presupuesto', $id)->delete();
        PresupuestoServicioTaller::where('id_presupuesto', $id)->delete();
        $presupuesto->delete();
        DB::commit();

        return $presupuesto;
    }
}

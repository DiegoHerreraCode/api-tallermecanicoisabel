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
use App\Services\PDFService;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service PresupuestoService
 * 
 * Gestiona el ciclo de vida de los presupuestos (iniciales y extras)
 * y su sincronización con ingresos y órdenes de trabajo.
 */
class PresupuestoService
{
    /**
     * Retorna el listado completo de presupuestos con sus detalles.
     */
    public static function getAll()
    {
        return Presupuesto::with([
            'repuestos.repuesto.marca',
            'serviciosTaller.servicioTaller',
            'ingreso.vehiculo.cliente',
            'ingreso.vehiculo.modelo.marca',
            'ingreso.vehiculo.tipoVehiculo',
            'ingreso.tipoOrden',
            'orden.cliente',
            'orden.tipoOrden',
            'admin'
        ])->get();
    }

    /**
     * Obtiene un presupuesto por su clave primaria con detalles.
     */
    public static function getOne($id)
    {
        return Presupuesto::with([
            'repuestos.repuesto.marca',
            'serviciosTaller.servicioTaller',
            'ingreso.vehiculo.cliente',
            'ingreso.vehiculo.modelo.marca',
            'ingreso.vehiculo.tipoVehiculo',
            'ingreso.tipoOrden',
            'orden.cliente',
            'orden.tipoOrden',
            'admin'
        ])->find($id);
    }

    /**
     * Registra un nuevo presupuesto (inicial o extra).
     * Si viene 'id_orden', se crea como presupuesto de tipo 'extra'.
     * Si no viene 'id_orden', se crea como presupuesto de tipo 'inicial'.
     */
    public static function create($data)
    {
        if (!empty($data['id_orden'])) {
            return self::createExtra($data);
        }

        return self::createInicial($data);
    }

    /**
     * Registra un presupuesto inicial.
     */
    public static function createInicial($data)
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
        $data['num_presupuesto'] = 'PRE-TEMP';
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
                'costo_unitario_base' => $repuesto['costo_unitario_base'] ?? null,
                'precio_unitario' => $repuesto['precio_unitario'],
                'porcentaje_ganancia' => $repuesto['porcentaje_ganancia'] ?? null,
                'cantidad' => $repuesto['cantidad'],
                'monto_total_linea_base' => $repuesto['monto_total_linea_base'] ?? null,
                'monto_total_linea' => $repuesto['monto_total_linea'],
            ]);
        }

        // Guardar servicios del presupuesto
        foreach ($array_servicios_taller as $servicioTaller) {
            PresupuestoServicioTaller::create([
                'id_presupuesto' => $presupuesto->id_presupuesto,
                'id_servicio_taller' => $servicioTaller['id_servicio_taller'],
                'costo_unitario_base' => $servicioTaller['costo_unitario_base'] ?? null,
                'precio_unitario' => $servicioTaller['precio_unitario'],
                'porcentaje_ganancia' => $servicioTaller['porcentaje_ganancia'] ?? null,
                'cantidad' => $servicioTaller['cantidad'],
                'monto_total_linea_base' => $servicioTaller['monto_total_linea_base'] ?? null,
                'monto_total_linea' => $servicioTaller['monto_total_linea'],
            ]);
        }

        // Actualizar el estado del ingreso a confirmando_presupuesto
        $ingreso->update([
            'estado' => IngresoEstado::CONFIRMANDO_PRESUPUESTO,
            'last_update' => now(),
        ]);

        DB::commit();

        // Generar automáticamente el PDF del presupuesto y almacenar su ruta
        $pdfPath = PDFService::generarPresupuestoPDF($presupuesto);
        if ($pdfPath) {
            $presupuesto->update(['pdf_presupuesto' => $pdfPath]);
        }

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Registra un presupuesto de tipo extra asociado a una orden existente.
     */
    public static function createExtra($data)
    {
        $idOrden = $data['id_orden'];
        $orden = Orden::find($idOrden);

        if (!$orden) {
            throw new Exception("La orden de trabajo especificada no existe.");
        }

        // Validar que la orden esté operativamente activa (no finalizada ni anulada)
        if ($orden->estado_operativo === OrdenEstadoOperativo::FINALIZADA->value) {
            throw new Exception("No se pueden emitir presupuestos extras para una orden que ya está finalizada.");
        }

        if ($orden->estado_operativo === OrdenEstadoOperativo::ANULADA->value) {
            throw new Exception("No se pueden emitir presupuestos extras para una orden anulada.");
        }

        // Validar que no exista otro presupuesto extra en estado pendiente para esta misma orden
        $extraPendiente = Presupuesto::where('id_orden', $idOrden)
            ->where('tipo', PresupuestoTipo::EXTRA->value)
            ->where('estado', PresupuestoEstado::PENDIENTE->value)
            ->first();

        if ($extraPendiente) {
            throw new Exception("La orden ya tiene un presupuesto extra pendiente de aprobación ({$extraPendiente->num_presupuesto}). Debe resolverse antes de crear uno nuevo.");
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

        // Asignar id_ingreso a partir de la orden
        $data['id_ingreso'] = $orden->id_ingreso;

        // Buscar el num_presupuesto del presupuesto inicial aceptado que dio origen a esta orden
        $presupuestoInicial = Presupuesto::where('id_orden', $idOrden)
            ->where('tipo', PresupuestoTipo::INICIAL->value)
            ->where('estado', PresupuestoEstado::ACEPTADO->value)
            ->first();

        $data['num_presupuesto_padre'] = $presupuestoInicial ? $presupuestoInicial->num_presupuesto : null;

        $now = now();
        $data['tipo'] = PresupuestoTipo::EXTRA;
        $data['estado'] = PresupuestoEstado::PENDIENTE;
        $data['fecha_creacion'] = $now;
        $data['fecha_vencimiento'] = (clone $now)->addDay();
        $data['num_presupuesto'] = 'PRE-TEMP';
        $data['pdf_presupuesto'] = $data['pdf_presupuesto'] ?? 'pdf-presupuesto-temp';

        $presupuesto = Presupuesto::create($data);

        // Formatear num_presupuesto con PRE-0000X
        $formattedNum = 'PRE-' . str_pad($presupuesto->id_presupuesto, 5, '0', STR_PAD_LEFT);
        $presupuesto->update([
            'num_presupuesto' => $formattedNum,
            'pdf_presupuesto' => "pdf-presupuesto-{$presupuesto->id_presupuesto}",
        ]);

        // Guardar repuestos adicionales
        foreach ($array_repuestos as $repuesto) {
            PresupuestoRepuesto::create([
                'id_presupuesto' => $presupuesto->id_presupuesto,
                'id_repuesto' => $repuesto['id_repuesto'],
                'costo_unitario_base' => $repuesto['costo_unitario_base'] ?? null,
                'precio_unitario' => $repuesto['precio_unitario'],
                'porcentaje_ganancia' => $repuesto['porcentaje_ganancia'] ?? null,
                'cantidad' => $repuesto['cantidad'],
                'monto_total_linea_base' => $repuesto['monto_total_linea_base'] ?? null,
                'monto_total_linea' => $repuesto['monto_total_linea'],
            ]);
        }

        // Guardar servicios adicionales
        foreach ($array_servicios_taller as $servicioTaller) {
            PresupuestoServicioTaller::create([
                'id_presupuesto' => $presupuesto->id_presupuesto,
                'id_servicio_taller' => $servicioTaller['id_servicio_taller'],
                'costo_unitario_base' => $servicioTaller['costo_unitario_base'] ?? null,
                'precio_unitario' => $servicioTaller['precio_unitario'],
                'porcentaje_ganancia' => $servicioTaller['porcentaje_ganancia'] ?? null,
                'cantidad' => $servicioTaller['cantidad'],
                'monto_total_linea_base' => $servicioTaller['monto_total_linea_base'] ?? null,
                'monto_total_linea' => $servicioTaller['monto_total_linea'],
            ]);
        }

        DB::commit();

        // Generar automáticamente el PDF del presupuesto y almacenar su ruta
        $pdfPath = PDFService::generarPresupuestoPDF($presupuesto);
        if ($pdfPath) {
            $presupuesto->update(['pdf_presupuesto' => $pdfPath]);
        }

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Acepta un presupuesto (inicial o extra).
     */
    public static function aceptar($id, $data = [])
    {
        $presupuesto = Presupuesto::with(['repuestos', 'serviciosTaller', 'ingreso.vehiculo'])->find($id);

        if (!$presupuesto) {
            return null;
        }

        if ($presupuesto->estado !== PresupuestoEstado::PENDIENTE->value) {
            throw new Exception("Solo se pueden aceptar presupuestos en estado 'pendiente'. El estado actual es '{$presupuesto->estado}'.");
        }

        if ($presupuesto->tipo === PresupuestoTipo::EXTRA->value) {
            return self::aceptarExtra($presupuesto);
        }

        return self::aceptarInicial($presupuesto, $data);
    }

    /**
     * Procesa la aceptación de un presupuesto inicial (creando la orden).
     */
    public static function aceptarInicial($presupuesto, $data)
    {
        if (empty($data['fecha_fin_tentativa'])) {
            throw new Exception("El campo 'fecha_fin_tentativa' es obligatorio para aceptar un presupuesto inicial.");
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
                'costo_unitario_base' => $repuesto->costo_unitario_base,
                'precio_unitario' => $repuesto->precio_unitario,
                'porcentaje_ganancia' => $repuesto->porcentaje_ganancia,
                'cantidad' => $repuesto->cantidad,
                'monto_total_linea_base' => $repuesto->monto_total_linea_base,
                'monto_total_linea' => $repuesto->monto_total_linea,
            ]);
        }

        // 3. Grabar los servicios en la orden con estado pendiente_asignacion
        foreach ($presupuesto->serviciosTaller as $servicioTaller) {
            OrdenServicioTaller::create([
                'id_orden' => $orden->id_orden,
                'id_servicio_taller' => $servicioTaller->id_servicio_taller,
                'id_personal' => null,
                'costo_unitario_base' => $servicioTaller->costo_unitario_base,
                'precio_unitario' => $servicioTaller->precio_unitario,
                'porcentaje_ganancia' => $servicioTaller->porcentaje_ganancia,
                'cantidad' => $servicioTaller->cantidad,
                'monto_total_linea_base' => $servicioTaller->monto_total_linea_base,
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
     * Procesa la aceptación de un presupuesto extra, incorporando los ítems a la orden existente.
     */
    public static function aceptarExtra($presupuesto)
    {
        $orden = Orden::find($presupuesto->id_orden);
        if (!$orden) {
            throw new Exception("La orden asociada al presupuesto extra no fue encontrada.");
        }

        if ($orden->estado_operativo === OrdenEstadoOperativo::FINALIZADA->value || $orden->estado_operativo === OrdenEstadoOperativo::ANULADA->value) {
            throw new Exception("No se puede aceptar el presupuesto extra porque la orden ya está {$orden->estado_operativo}.");
        }

        DB::beginTransaction();

        // 1. Incorporar repuestos extras a la orden
        foreach ($presupuesto->repuestos as $itemExtra) {
            $existente = OrdenRepuesto::where('id_orden', $orden->id_orden)
                ->where('id_repuesto', $itemExtra->id_repuesto)
                ->first();

            if ($existente) {
                $nuevaCantidad = $existente->cantidad + $itemExtra->cantidad;
                $nuevoTotalLinea = $existente->monto_total_linea + $itemExtra->monto_total_linea;
                $nuevoTotalLineaBase = ($existente->monto_total_linea_base !== null || $itemExtra->monto_total_linea_base !== null)
                    ? (($existente->monto_total_linea_base ?? 0) + ($itemExtra->monto_total_linea_base ?? 0))
                    : null;

                $existente->update([
                    'costo_unitario_base' => $itemExtra->costo_unitario_base ?? $existente->costo_unitario_base,
                    'porcentaje_ganancia' => $itemExtra->porcentaje_ganancia ?? $existente->porcentaje_ganancia,
                    'cantidad' => $nuevaCantidad,
                    'monto_total_linea_base' => $nuevoTotalLineaBase,
                    'monto_total_linea' => $nuevoTotalLinea,
                ]);
            } else {
                OrdenRepuesto::create([
                    'id_orden' => $orden->id_orden,
                    'id_repuesto' => $itemExtra->id_repuesto,
                    'costo_unitario_base' => $itemExtra->costo_unitario_base,
                    'precio_unitario' => $itemExtra->precio_unitario,
                    'porcentaje_ganancia' => $itemExtra->porcentaje_ganancia,
                    'cantidad' => $itemExtra->cantidad,
                    'monto_total_linea_base' => $itemExtra->monto_total_linea_base,
                    'monto_total_linea' => $itemExtra->monto_total_linea,
                ]);
            }
        }

        // 2. Incorporar servicios extras a la orden
        foreach ($presupuesto->serviciosTaller as $itemExtra) {
            $existente = OrdenServicioTaller::where('id_orden', $orden->id_orden)
                ->where('id_servicio_taller', $itemExtra->id_servicio_taller)
                ->first();

            if ($existente) {
                $nuevaCantidad = $existente->cantidad + $itemExtra->cantidad;
                $nuevoTotalLinea = $existente->monto_total_linea + $itemExtra->monto_total_linea;
                $nuevoTotalLineaBase = ($existente->monto_total_linea_base !== null || $itemExtra->monto_total_linea_base !== null)
                    ? (($existente->monto_total_linea_base ?? 0) + ($itemExtra->monto_total_linea_base ?? 0))
                    : null;

                // Conserva personal asignado y estado operativo actual
                $existente->update([
                    'costo_unitario_base' => $itemExtra->costo_unitario_base ?? $existente->costo_unitario_base,
                    'porcentaje_ganancia' => $itemExtra->porcentaje_ganancia ?? $existente->porcentaje_ganancia,
                    'cantidad' => $nuevaCantidad,
                    'monto_total_linea_base' => $nuevoTotalLineaBase,
                    'monto_total_linea' => $nuevoTotalLinea,
                ]);
            } else {
                OrdenServicioTaller::create([
                    'id_orden' => $orden->id_orden,
                    'id_servicio_taller' => $itemExtra->id_servicio_taller,
                    'id_personal' => null,
                    'costo_unitario_base' => $itemExtra->costo_unitario_base,
                    'precio_unitario' => $itemExtra->precio_unitario,
                    'porcentaje_ganancia' => $itemExtra->porcentaje_ganancia,
                    'cantidad' => $itemExtra->cantidad,
                    'monto_total_linea_base' => $itemExtra->monto_total_linea_base,
                    'monto_total_linea' => $itemExtra->monto_total_linea,
                    'estado' => OrdenServicioTallerEstado::PENDIENTE_ASIGNACION,
                    'fecha_inicio' => null,
                    'fecha_fin' => null,
                ]);
            }
        }

        // 3. Recalcular montos económicos de la orden
        $nuevoSubtotal = $orden->monto_subtotal + $presupuesto->monto_subtotal;
        $nuevoMontoIva = $orden->monto_iva + $presupuesto->monto_iva;
        $nuevoTotal = $orden->monto_total + $presupuesto->monto_total;
        $nuevoPendiente = $orden->monto_pendiente + $presupuesto->monto_total;

        $orden->update([
            'monto_subtotal' => $nuevoSubtotal,
            'monto_iva' => $nuevoMontoIva,
            'monto_total' => $nuevoTotal,
            'monto_pendiente' => $nuevoPendiente,
            'last_update' => now(),
        ]);

        // 4. Marcar presupuesto extra como aceptado
        $presupuesto->update([
            'estado' => PresupuestoEstado::ACEPTADO,
        ]);

        DB::commit();

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Rechaza un presupuesto (inicial o extra).
     * Si es inicial, el ingreso vuelve a 'esperando_orden'.
     * Si es extra, ni la orden ni el ingreso se alteran.
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

        // Solo si es de tipo inicial el ingreso regresa a esperando_orden
        if ($presupuesto->tipo === PresupuestoTipo::INICIAL->value && $presupuesto->ingreso) {
            $presupuesto->ingreso->update([
                'estado' => IngresoEstado::ESPERANDO_ORDEN,
                'last_update' => now(),
            ]);
        }

        DB::commit();

        return self::getOne($presupuesto->id_presupuesto);
    }

    /**
     * Anula un presupuesto (inicial o extra).
     * Si es inicial, el ingreso vuelve a 'esperando_orden'.
     * Si es extra, ni la orden ni el ingreso se alteran.
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

        // Solo si es de tipo inicial y estaba confirmando_presupuesto, regresa a esperando_orden
        if ($presupuesto->tipo === PresupuestoTipo::INICIAL->value && $presupuesto->ingreso && $presupuesto->ingreso->estado === IngresoEstado::CONFIRMANDO_PRESUPUESTO->value) {
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

    /**
     * Actualiza automáticamente a 'rechazado' todos los presupuestos con estado 'pendiente'
     * cuya fecha de vencimiento sea menor o igual al día de hoy.
     *
     * @return int Cantidad de presupuestos actualizados.
     */
    public static function actualizarPresupuestosVencidos(): int
    {
        $hoy = now()->toDateString();

        // Consulta SQL Raw equivalente:
        // UPDATE "presupuestos" SET "estado" = 'rechazado'
        // WHERE "estado" = 'pendiente' AND "fecha_vencimiento" <= :hoy;
        return Presupuesto::where('estado', PresupuestoEstado::PENDIENTE)
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', $hoy)
            ->update([
                'estado' => PresupuestoEstado::RECHAZADO,
            ]);
    }
}

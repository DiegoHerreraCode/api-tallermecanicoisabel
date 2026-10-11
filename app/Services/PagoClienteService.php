<?php

namespace App\Services;

use App\Models\PagoCliente;
use App\Models\Orden;
use App\Enums\OrdenEstadoAdministrativo;
use Illuminate\Support\Facades\DB;

/**
 * Service PagoClienteService
 * 
 * Gestiona los cobros y amortizaciones realizados por los clientes sobre sus �rdenes de trabajo:
 * - Valida que las �rdenes se puedan cobrar tras haber finalizado el trabajo operativo.
 * - Registra pagos y descuenta autom�ticamente el monto pendiente de la orden.
 * - Actualiza el estado administrativo a 'pagada' si el saldo pendiente llega a cero.
 */
class PagoClienteService
{
    /**
     * Retorna todos los pagos de clientes registrados.
     * Consulta SQL Raw:
     * SELECT * FROM pagos_clientes;
     */
    public static function getAll()
    {
        return PagoCliente::get();
    }

    /**
     * Obtiene un pago de cliente por su ID.
     * Consulta SQL Raw:
     * SELECT * FROM pagos_clientes WHERE id_pago_cliente = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return PagoCliente::find($id);
    }

    /**
     * Obtiene el historial de pagos asociados a una orden de trabajo junto a los datos del cliente.
     * Consulta SQL Raw:
     * SELECT * FROM pagos_clientes WHERE id_orden = $idOrden;
     * SELECT * FROM clientes WHERE id_cliente IN (...);
     */
    public static function getByOrden($idOrden)
    {
        // Consulta SQL Raw equivalente:
        // SELECT * FROM "pagos_clientes" WHERE "id_orden" = :idOrden;
        return PagoCliente::with('cliente')->where('id_orden', $idOrden)->get();
    }

    /**
     * Registra un pago de cliente:
     * 1. Verifica que si la orden ya est� finalizada operativamente.
     * 2. Inserta el pago en la base de datos.
     * 3. Suma los pagos activos y recalcula el saldo pendiente de la orden.
     * 4. Transiciona a 'pagada' si el saldo queda en cero.
     *
     * Consulta SQL Raw:
     * SELECT * FROM ordenes WHERE id_orden = ... LIMIT 1;
     * INSERT INTO pagos_clientes (id_cliente, id_orden, monto, ...) VALUES (...);
     * SELECT SUM(monto) FROM pagos_clientes WHERE id_orden = ... AND estado != 'anulado';
     * UPDATE ordenes SET monto_pendiente = ..., estado_administrativo = ... WHERE id_orden = ...;
     */
    public static function create($data)
    {
        // Solo se permite registrar pagos cuando el estado operativo es finalizada
        $ordenCheck = Orden::find($data['id_orden'] ?? null);
        if ($ordenCheck) {
            $estadoOperativo = $ordenCheck->estado_operativo instanceof \App\Enums\OrdenEstadoOperativo
                ? $ordenCheck->estado_operativo->value
                : (string) $ordenCheck->estado_operativo;

            if ($estadoOperativo !== 'finalizada') {
                return false;
            }
        }

        DB::beginTransaction();

        if (!empty($data['fecha_pago'])) {
            $data['fecha_pago'] = strlen($data['fecha_pago']) === 10
                ? $data['fecha_pago'] . ' ' . now()->format('H:i:s')
                : $data['fecha_pago'];
        } else {
            $data['fecha_pago'] = now();
        }

        $pago = PagoCliente::create($data);

        // Actualizar saldo pendiente de la orden y su estado administrativo
        $orden = Orden::find($pago->id_orden);
        if ($orden) {
            // Consulta SQL Raw equivalente:
            // SELECT COALESCE(SUM("monto"), 0) FROM "pagos_clientes" WHERE "id_orden" = :id_orden;
            $totalPagado = (float) PagoCliente::where('id_orden', $orden->id_orden)->sum('monto');
            $nuevoPendiente = max(0, (float) $orden->monto_total - $totalPagado);

            //actualizar el estado administrativo de la orden a pendiente_pago/parcialmente_pagada/pagada segun corresponda
            if ($nuevoPendiente <= 0) {
                $estadoAdmin = OrdenEstadoAdministrativo::PAGADA;
            } elseif ($nuevoPendiente > 0 && $nuevoPendiente < $orden->monto_total) {
                $estadoAdmin = OrdenEstadoAdministrativo::PARCIALMENTE_PAGADA;
            } else {
                $estadoAdmin = OrdenEstadoAdministrativo::PENDIENTE_PAGO;
            }

            $orden->update([
                'monto_pendiente' => round($nuevoPendiente, 2),
                'estado_administrativo' => $estadoAdmin,
                'last_update' => now(),
            ]);
        }

        DB::commit();
        return $pago;
    }

    /**
     * Modifica los datos de un pago y recalcula el saldo de la orden.
     * Consulta SQL Raw:
     * UPDATE pagos_clientes SET monto = ..., metodo_pago = ... WHERE id_pago_cliente = $id;
     * SELECT SUM(monto) FROM pagos_clientes WHERE id_orden = ...;
     * UPDATE ordenes SET monto_pendiente = ..., estado_administrativo = ... WHERE id_orden = ...;
     */
    public static function update($id, $data)
    {
        $pago = PagoCliente::find($id);
        if (!$pago) {
            return null;
        }

        DB::beginTransaction();
        $pago->update($data);

        // Recalcular saldo pendiente de la orden si se modific el monto
        $orden = Orden::find($pago->id_orden);
        if ($orden) {
            // Consulta SQL Raw equivalente:
            // SELECT COALESCE(SUM("monto"), 0) FROM "pagos_clientes" WHERE "id_orden" = :id_orden;
            $totalPagado = (float) PagoCliente::where('id_orden', $orden->id_orden)->sum('monto');
            $nuevoPendiente = max(0, (float) $orden->monto_total - $totalPagado);

            //actualizar el estado administrativo de la orden a pendiente_pago/parcialmente_pagada/pagada segun corresponda
            if ($nuevoPendiente <= 0) {
                $estadoAdmin = OrdenEstadoAdministrativo::PAGADA;
            } elseif ($nuevoPendiente > 0 && $nuevoPendiente < $orden->monto_total) {
                $estadoAdmin = OrdenEstadoAdministrativo::PARCIALMENTE_PAGADA;
            } else {
                $estadoAdmin = OrdenEstadoAdministrativo::PENDIENTE_PAGO;
            }

            $orden->update([
                'monto_pendiente' => round($nuevoPendiente, 2),
                'estado_administrativo' => $estadoAdmin,
                'last_update' => now(),
            ]);
        }

        DB::commit();
        return $pago;
    }

    /**
     * Elimina f�sicamente un pago y reajusta el saldo de la orden asociada.
     * Consulta SQL Raw:
     * DELETE FROM pagos_clientes WHERE id_pago_cliente = $id;
     * SELECT SUM(monto) FROM pagos_clientes WHERE id_orden = ...;
     * UPDATE ordenes SET monto_pendiente = ..., estado_administrativo = ... WHERE id_orden = ...;
     */
    public static function delete($id)
    {
        $pago = PagoCliente::find($id);
        if (!$pago) {
            return null;
        }

        DB::beginTransaction();
        $idOrden = $pago->id_orden;
        $pago->delete();

        // Recalcular saldo pendiente de la orden tras eliminar el pago
        $orden = Orden::find($idOrden);
        if ($orden) {
            // Consulta SQL Raw equivalente:
            // SELECT COALESCE(SUM("monto"), 0) FROM "pagos_clientes" WHERE "id_orden" = :idOrden;
            $totalPagado = (float) PagoCliente::where('id_orden', $idOrden)->sum('monto');
            $nuevoPendiente = max(0, (float) $orden->monto_total - $totalPagado);

            //actualizar el estado administrativo de la orden a pendiente_pago/parcialmente_pagada/pagada segun corresponda
            if ($nuevoPendiente <= 0) {
                $estadoAdmin = OrdenEstadoAdministrativo::PAGADA;
            } elseif ($nuevoPendiente > 0 && $nuevoPendiente < $orden->monto_total) {
                $estadoAdmin = OrdenEstadoAdministrativo::PARCIALMENTE_PAGADA;
            } else {
                $estadoAdmin = OrdenEstadoAdministrativo::PENDIENTE_PAGO;
            }

            $orden->update([
                'monto_pendiente' => round($nuevoPendiente, 2),
                'estado_administrativo' => $estadoAdmin,
                'last_update' => now(),
            ]);
        }

        DB::commit();
        return $pago;
    }
}

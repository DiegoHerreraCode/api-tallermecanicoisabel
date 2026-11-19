<?php

namespace App\Services;

use App\Models\PagoCliente;
use App\Models\Orden;
use App\Enums\OrdenEstadoAdministrativo;
use Illuminate\Support\Facades\DB;

/**
 * Service PagoClienteService
 * 
 * Gestiona los cobros y amortizaciones realizados por los clientes sobre sus órdenes de trabajo:
 * - Valida que solo se puedan pagar órdenes que no estén anuladas y que no estén finalizadas y pagadas a la vez.
 * - Registra pagos y recalcula automáticamente el saldo pendiente y monto a favor de la orden.
 * - Actualiza el estado administrativo a 'pagada' si el saldo pendiente llega a cero.
 */
class PagoClienteService
{
    /**
     * Retorna todos los pagos de clientes registrados.
     */
    public static function getAll()
    {
        return PagoCliente::get();
    }

    /**
     * Obtiene un pago de cliente por su ID.
     */
    public static function getOne($id)
    {
        return PagoCliente::find($id);
    }

    /**
     * Obtiene el historial de pagos asociados a una orden de trabajo junto a los datos del cliente.
     */
    public static function getByOrden($idOrden)
    {
        return PagoCliente::with('cliente')->where('id_orden', $idOrden)->get();
    }

    /**
     * Registra un pago de cliente:
     * 1. Verifica que la orden no esté anulada y que no esté finalizada y pagada simultáneamente.
     * 2. Inserta el pago en la base de datos.
     * 3. Recalcula el saldo de la orden y su monto a favor.
     */
    public static function create($data)
    {
        $ordenCheck = Orden::find($data['id_orden'] ?? null);
        if ($ordenCheck) {
            $estadoOperativo = $ordenCheck->estado_operativo instanceof \App\Enums\OrdenEstadoOperativo
                ? $ordenCheck->estado_operativo->value
                : (string) $ordenCheck->estado_operativo;

            $estadoAdministrativo = $ordenCheck->estado_administrativo instanceof \App\Enums\OrdenEstadoAdministrativo
                ? $ordenCheck->estado_administrativo->value
                : (string) $ordenCheck->estado_administrativo;

            // No se pueden pagar órdenes anuladas
            if ($estadoOperativo === 'anulada') {
                return false;
            }

            // Tampoco si están finalizadas Y pagadas al mismo tiempo
            if ($estadoOperativo === 'finalizada' && $estadoAdministrativo === 'pagada') {
                return false;
            }
        } else {
            return null;
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
        self::recalcularBalanceOrden($ordenCheck->id_orden);

        DB::commit();
        return $pago;
    }

    /**
     * Modifica los datos de un pago y recalcula el saldo de la orden.
     */
    public static function update($id, $data)
    {
        $pago = PagoCliente::find($id);
        if (!$pago) {
            return null;
        }

        DB::beginTransaction();
        $pago->update($data);

        // Recalcular saldo de la orden
        self::recalcularBalanceOrden($pago->id_orden);

        DB::commit();
        return $pago;
    }

    /**
     * Elimina físicamente un pago y reajusta el saldo de la orden asociada.
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

        // Recalcular saldo de la orden
        self::recalcularBalanceOrden($idOrden);

        DB::commit();
        return $pago;
    }

    /**
     * Recalcula montos pendientes, montos a favor y estado administrativo de la orden
     */
    public static function recalcularBalanceOrden($idOrden)
    {
        $orden = Orden::with(['repuestos', 'serviciosTaller'])->find($idOrden);
        if (!$orden) {
            return;
        }

        // 1. Total pagado en la orden
        $totalPagado = (float) PagoCliente::where('id_orden', $orden->id_orden)->sum('monto');

        // 2. Monto cobrable al cliente (excluye las cantidades cubiertas por garantía)
        // El IVA se aplica únicamente sobre el subtotal cobrable (no cubierto por garantía)
        $subtotalSinGarantia = 0.0;

        foreach ($orden->repuestos as $repuesto) {
            $cantidadTotal = (int) ($repuesto->cantidad ?? 0);
            $cantGarantia = min((int) ($repuesto->cantidad_cubierta_garantia ?? 0), $cantidadTotal);
            $cantCobrable = max(0, $cantidadTotal - $cantGarantia);
            $precioUnit = (float) ($repuesto->precio_unitario ?? 0);
            $subtotalSinGarantia += ($cantCobrable * $precioUnit);
        }

        foreach ($orden->serviciosTaller as $servicio) {
            $cantidadTotal = (int) ($servicio->cantidad ?? 0);
            $cantGarantia = min((int) ($servicio->cantidad_cubierta_garantia ?? 0), $cantidadTotal);
            $cantCobrable = max(0, $cantidadTotal - $cantGarantia);
            $precioUnit = (float) ($servicio->precio_unitario ?? 0);
            $subtotalSinGarantia += ($cantCobrable * $precioUnit);
        }

        $porcentajeIva = (float) ($orden->porcentaje_iva ?? 0);
        $ivaSinGarantia = round($subtotalSinGarantia * ($porcentajeIva / 100), 2);
        $montoCobrable = round($subtotalSinGarantia + $ivaSinGarantia, 2);

        // Diferencia: lo exigible al cliente menos lo pagado
        $balance = round($montoCobrable - $totalPagado, 2);

        if ($balance <= 0) {
            $nuevoMontoPorCobrar = 0.00;
            $nuevoMontoAFavor = abs($balance);
            $estadoAdmin = OrdenEstadoAdministrativo::PAGADA;
        } else {
            $nuevoMontoPorCobrar = $balance;
            $nuevoMontoAFavor = 0.00;
            $estadoAdmin = $totalPagado > 0
                ? OrdenEstadoAdministrativo::PARCIALMENTE_PAGADA
                : OrdenEstadoAdministrativo::PENDIENTE_PAGO;
        }

        $orden->update([
            'monto_subtotal' => $subtotalSinGarantia,
            'monto_iva' => $ivaSinGarantia,
            'monto_total' => $montoCobrable,
            'monto_por_cobrar' => $nuevoMontoPorCobrar,
            'monto_a_favor' => $nuevoMontoAFavor,
            'estado_administrativo' => $estadoAdmin,
            'last_update' => now(),
        ]);
    }
}

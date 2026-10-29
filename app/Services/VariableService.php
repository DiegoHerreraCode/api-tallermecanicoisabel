<?php

namespace App\Services;

use App\Models\Repuesto;
use App\Models\ServicioTaller;
use App\Models\Variable;
use Illuminate\Support\Facades\DB;

/**
 * Service variableService
 * 
 * Gestiona las variables del sistema.
 */
class VariableService
{
    /**
     * Retorna el listado completo de variables.
     * Consulta SQL Raw:
     * SELECT * FROM variables;
     */
    public static function getAll()
    {
        return Variable::get();
    }

    /**
     * Obtiene una variable por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM variables WHERE id_variable = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return Variable::find($id);
    }

    /**
     * Registra una nueva variable del sistema.
     * Consulta SQL Raw:
     * INSERT INTO variables (nombre, valor, tipo) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $variable = Variable::create($data);
        DB::commit();
        return $variable;
    }

    /**
     * Modifica una variable del sistema.
     * Consulta SQL Raw:
     * UPDATE variables SET nombre = ..., valor = ..., tipo = ... WHERE id_variable = $id;
     */
    public static function update($id, $data)
    {
        $variable = Variable::find($id);
        if (!$variable) {
            return null;
        }

        DB::beginTransaction();
        try {
            $variable->update($data);
            $variable->refresh();

            // Si la variable modificada es 'calculo_ganancia', recalcular monto_venta y utilidad en repuestos y servicios_taller
            if (isset($data['valor']) && $variable->nombre === 'calculo_ganancia') {
                self::recalcularPrecios($data['valor']);
            }

            DB::commit();
            return $variable;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Recalcula monto_venta y utilidad de repuestos y servicios_taller según el modo de ganancia:
     * - "sobre_venta":
     *     monto_venta = costo / (1 - (porcentaje_ganancia / 100))
     *     utilidad    = monto_venta - costo
     * - "sobre_costo":
     *     monto_venta = costo * (1 + (porcentaje_ganancia / 100))
     *     utilidad    = monto_venta - costo
     *
     * @param string $modoCalculo "sobre_venta" | "sobre_costo"
     * @return void
     */
    public static function recalcularPrecios(string $modoCalculo): void
    {
        if (!in_array($modoCalculo, ['sobre_venta', 'sobre_costo'])) {
            return;
        }

        // 1. Recalcular Repuestos
        $repuestos = Repuesto::all();
        foreach ($repuestos as $repuesto) {
            $costo = (float) $repuesto->ultimo_costo_compra_unitario;
            $porcentaje = (float) $repuesto->porcentaje_ganancia;

            if ($modoCalculo === 'sobre_venta') {
                $divisor = 1 - ($porcentaje / 100);
                $montoVenta = $divisor > 0 ? ($costo / $divisor) : 0;
            } else {
                $montoVenta = $costo * (1 + ($porcentaje / 100));
            }

            $montoVenta = round($montoVenta, 2);
            $utilidad = round($montoVenta - $costo, 2);

            $repuesto->update([
                'monto_venta' => $montoVenta,
                'utilidad' => $utilidad,
            ]);
        }

        // 2. Recalcular Servicios de Taller
        $servicios = ServicioTaller::all();
        foreach ($servicios as $servicio) {
            $costo = (float) $servicio->costo;
            $porcentaje = (float) $servicio->porcentaje_ganancia;

            if ($modoCalculo === 'sobre_venta') {
                $divisor = 1 - ($porcentaje / 100);
                $montoVenta = $divisor > 0 ? ($costo / $divisor) : 0;
            } else {
                $montoVenta = $costo * (1 + ($porcentaje / 100));
            }

            $montoVenta = round($montoVenta, 2);
            $utilidad = round($montoVenta - $costo, 2);

            $servicio->update([
                'monto_venta' => $montoVenta,
                'utilidad' => $utilidad,
            ]);
        }
    }

    /**
     * Elimina una variable del sistema de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM variables WHERE id_variable = $id;
     */
    public static function delete($id)
    {
        $variable = Variable::find($id);
        if (!$variable) {
            return null;
        }

        DB::beginTransaction();
        $variable->delete();
        DB::commit();
        return $variable;
    }
}

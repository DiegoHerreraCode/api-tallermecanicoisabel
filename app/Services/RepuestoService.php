<?php

namespace App\Services;

use App\Models\Repuesto;
use Illuminate\Support\Facades\DB;

/**
 * Service RepuestoService
 * 
 * Gestiona el catalogo de repuestos.
 */
class RepuestoService
{
    /**
     * Retorna el listado completo de repuestos.
     * Consulta SQL Raw:
     * SELECT * FROM repuestos;
     */
    public static function getAll()
    {
        return Repuesto::get();
    }

    /**
     * Obtiene un modelo por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM repuestos WHERE id_repuesto = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return Repuesto::find($id);
    }

    /**
     * Registra un nuevo repuesto.
     * Consulta SQL Raw:
     * INSERT INTO repuestos (id_marca_repuesto, nombre_modelo, ultimo_costo_compra_unitario, monto_venta, utilidad, porcentaje_ganancia) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $repuesto = Repuesto::create($data);
        DB::commit();
        return $repuesto;
    }

    /**
     * Modifica un repuesto.
     * Consulta SQL Raw:
     * UPDATE repuestos SET nombre_modelo = ..., ultimo_costo_compra_unitario = ..., monto_venta = ..., utilidad = ..., porcentaje_ganancia = ... WHERE id_repuesto = $id;
     */
    public static function update($id, $data)
    {
        $repuesto = Repuesto::find($id);
        if (!$repuesto) {
            return null;
        }

        DB::beginTransaction();
        $repuesto->update($data);
        DB::commit();
        return $repuesto;
    }

    /**
     * Elimina un repuesto de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM repuestos WHERE id_repuesto = $id;
     */
    public static function delete($id)
    {
        $repuesto = Repuesto::find($id);
        if (!$repuesto) {
            return null;
        }

        DB::beginTransaction();
        $repuesto->delete();
        DB::commit();
        return $repuesto;
    }
}

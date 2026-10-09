<?php

namespace App\Services;

use App\Models\MarcaRepuesto;
use Illuminate\Support\Facades\DB;

/**
 * Service MarcaRepuestoService
 * 
 * Gestiona el catlogo de marcas de repuestos.
 */
class MarcaRepuestoService
{
    /**
     * Retorna el listado completo de marcas de repuestos.
     * Consulta SQL Raw:
     * SELECT * FROM marcas_repuestos;
     */
    public static function getAll()
    {
        return MarcaRepuesto::get();
    }

    /**
     * Obtiene una marca por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM marcas_repuestos WHERE id_marca_repuesto = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return MarcaRepuesto::find($id);
    }

    /**
     * Registra una nueva marca de repuesto.
     * Consulta SQL Raw:
     * INSERT INTO marcas_repuestos (nombre) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $marca = MarcaRepuesto::create($data);
        DB::commit();
        return $marca;
    }

    /**
     * Modifica el nombre de una marca de repuesto.
     * Consulta SQL Raw:
     * UPDATE marcas_repuestos SET nombre = ... WHERE id_marca_repuesto = $id;
     */
    public static function update($id, $data)
    {
        $marca = MarcaRepuesto::find($id);
        if (!$marca) {
            return null;
        }

        DB::beginTransaction();
        $marca->update($data);
        DB::commit();
        return $marca;
    }

    /**
     * Elimina una marca de repuesto de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM marcas_repuestos WHERE id_marca_repuesto = $id;
     */
    public static function delete($id)
    {
        $marca = MarcaRepuesto::find($id);
        if (!$marca) {
            return null;
        }

        DB::beginTransaction();
        $marca->delete();
        DB::commit();
        return $marca;
    }
}

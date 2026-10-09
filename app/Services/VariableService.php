<?php

namespace App\Services;

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
        $variable->update($data);
        DB::commit();
        return $variable;
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

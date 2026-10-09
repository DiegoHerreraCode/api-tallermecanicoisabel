<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

/**
 * Service AdminService
 * 
 * Gestiona la l�gica de negocio para la administraci�n del personal directivo/administrativo.
 */
class AdminService
{
    /**
     * Obtiene el listado completo de administradores junto con su usuario de autenticaci�n.
     * Consulta SQL Raw:
     * SELECT * FROM admins;
     * SELECT * FROM users WHERE id IN (...);
     */
    public static function getAll()
    {
        return Admin::with('user')->get();
    }

    /**
     * Busca un administrador por su identificador primario.
     * Consulta SQL Raw:
     * SELECT * FROM admins WHERE id_admin = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return Admin::with('user')->find($id);
    }

    /**
     * Registra un nuevo administrador en el sistema dentro de una transacci�n.
     * Consulta SQL Raw:
     * INSERT INTO admins (nombre, cedula, num_tlf, direccion, id_user) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        $admin = Admin::create($data);
        DB::commit();
        return $admin;
    }

    /**
     * Actualiza la informaci�n personal o de contacto de un administrador.
     * Consulta SQL Raw:
     * UPDATE admins SET nombre = ..., cedula = ..., num_tlf = ..., direccion = ... WHERE id_admin = $id;
     */
    public static function update($id, $data)
    {
        $admin = Admin::find($id);
        if (!$admin) {
            return null;
        }

        DB::beginTransaction();
        $admin->update($data);
        DB::commit();
        return $admin;
    }

    /**
     * Elimina el registro de un administrador del sistema.
     * Consulta SQL Raw:
     * DELETE FROM admins WHERE id_admin = $id;
     */
    public static function delete($id)
    {
        $admin = Admin::find($id);
        if (!$admin) {
            return null;
        }

        DB::beginTransaction();
        $admin->delete();
        DB::commit();
        return $admin;
    }
}

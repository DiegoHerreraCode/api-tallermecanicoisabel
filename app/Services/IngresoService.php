<?php

namespace App\Services;

use App\Models\Ingreso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Enums\IngresoEstado;
use App\Models\Admin;
/**
 * Service IngresoService
 * 
 * Gestiona el catalogo de ingresos de vehiculos en el taller.
 */
class IngresoService
{
    /**
     * Retorna el listado completo de ingresos de vehiculos en el taller.
     * Consulta SQL Raw:
     * SELECT * FROM ingresos;
     */
    public static function getAll()
    {
        return Ingreso::get();
    }

    /**
     * Obtiene un ingreso por su clave primaria.
     * Consulta SQL Raw:
     * SELECT * FROM ingresos WHERE id_ingreso = $id LIMIT 1;
     */
    public static function getOne($id)
    {
        return Ingreso::find($id);
    }

    /**
     * Registra un nuevo ingreso de vehiculo.
     * Consulta SQL Raw:
     * INSERT INTO ingresos (id_tipo_orden, id_vehiculo, id_admin, fecha_ingreso, estado, observaciones_cliente, pdf_checklist, last_update, fecha_anulacion, id_admin_anulacion, motivo_anulacion) VALUES (...);
     */
    public static function create($data)
    {
        DB::beginTransaction();
        if (empty($data['id_admin'])) {
            $userId = auth()->id();
            // Consulta SQL Raw equivalente:
            // SELECT * FROM "admins" WHERE "id_user" = :userId LIMIT 1;
            $admin = $userId ? Admin::where('id_user', $userId)->first() : null;
            $data['id_admin'] = $admin ? $admin->id_admin : 1;
        }
        $data['fecha_ingreso'] = now();
        $data['estado'] = IngresoEstado::CHECKLIST_PENDIENTE;
        $data['last_update'] = now();
        $ingreso = Ingreso::create($data);
        DB::commit();
        return $ingreso;
    }

    /**
     * Modifica un ingreso de la base de datos.
     * Consulta SQL Raw:
     * UPDATE ingresos SET ... WHERE id_ingreso = $id;
     */
    public static function update($id, $data)
    {
        $ingreso = Ingreso::find($id);
        if (!$ingreso) {
            return null;
        }

        // Si se sube un archivo para el checklist
        if (isset($data['pdf_checklist'])) {
            if ($data['pdf_checklist'] instanceof UploadedFile) {
                $file = $data['pdf_checklist'];
                $nombreArchivo = 'checklist_ingreso_' . $ingreso->id_ingreso . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('checklists', $nombreArchivo, 'public');

                // Eliminar archivo anterior si existía físicamente
                if (!empty($ingreso->pdf_checklist) && Storage::disk('public')->exists($ingreso->pdf_checklist)) {
                    Storage::disk('public')->delete($ingreso->pdf_checklist);
                }

                $data['pdf_checklist'] = $path;
            }

            // Al cargar el checklist el estado pasa a esperando_orden
            $data['estado'] = IngresoEstado::ESPERANDO_ORDEN;
        }

        if (!empty($data['motivo_anulacion'])) {
            if ($ingreso->estado === IngresoEstado::CONFIRMANDO_PRESUPUESTO->value) {
                throw new \Exception("No se puede anular el ingreso mientras esté en estado 'confirmando_presupuesto'. Primero debe resolverse el presupuesto.");
            }
            $data['estado'] = IngresoEstado::ANULADO;
            $data['fecha_anulacion'] = now();
            if (empty($data['id_admin_anulacion'])) {
                $userId = auth()->id();
                // Consulta SQL Raw equivalente:
                // SELECT * FROM "admins" WHERE "id_user" = :userId LIMIT 1;
                $admin = $userId ? Admin::where('id_user', $userId)->first() : null;
                $data['id_admin_anulacion'] = $admin ? $admin->id_admin : 1;
            }
        }

        $data['last_update'] = now();

        DB::beginTransaction();
        $ingreso->update($data);
        DB::commit();
        return $ingreso;
    }

    /**
     * Elimina un ingreso de la base de datos.
     * Consulta SQL Raw:
     * DELETE FROM ingresos WHERE id_ingreso = $id;
     */
    public static function delete($id)
    {
        $ingreso = Ingreso::find($id);
        if (!$ingreso) {
            return null;
        }

        // Eliminar archivo de checklist si existe
        if (!empty($ingreso->pdf_checklist) && Storage::disk('public')->exists($ingreso->pdf_checklist)) {
            Storage::disk('public')->delete($ingreso->pdf_checklist);
        }

        DB::beginTransaction();
        $ingreso->delete();
        DB::commit();
        return $ingreso;
    }
}

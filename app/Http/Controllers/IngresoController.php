<?php

namespace App\Http\Controllers;

use App\Services\IngresoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\IngresoEstado;

/**
 * Class IngresoController
 *
 * Controlador RESTful encargado de gestionar los ingresos de vehiculos.
 * Expone las operaciones CRUD comunicándose directamente con IngresoService.
 */
class IngresoController extends Controller
{
    /**
     * Retorna todas los ingresos de vehiculos registrados en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de ingresos mediante IngresoService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "ingresos";
     *
     * @return JsonResponse Lista de ingresos.
     */
    public function index(): JsonResponse
    {
        $ingresos = IngresoService::getAll();
        return $this->successResponse(
            $ingresos,
            $ingresos->isEmpty() ? 'No se encontraron ingresos' : 'Ingresos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo ingreso de vehiculo.
     *
     * Lógica:
     * 1. Valida que el tipo de orden, vehiculo, admin, fecha de ingreso, estado, observaciones del cliente, pdf checklist, last update, fecha de anulacion, admin de anulacion y motivo de anulacion sean una cadena obligatoria de hasta 100 caracteres.
     * 2. Llama a IngresoService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna el ingreso creado con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "ingresos" ("id_tipo_orden", "id_vehiculo", "id_admin", "fecha_ingreso", "estado", "observaciones_cliente", "pdf_checklist", "last_update", "fecha_anulacion", "id_admin_anulacion", "motivo_anulacion") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_tipo_orden' => 'required|exists:tipos_ordenes,id_tipo_orden',
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'observaciones_cliente' => 'nullable|string|max:10000',
        ]);

        $data = $request->all();

        $ingreso = IngresoService::create($data);
        if (!$ingreso) {
            return $this->errorResponse('Ingreso no creado', 404);
        }
        return $this->successResponse($ingreso, 'Ingreso creado correctamente', 201);
    }

    /**
     * Obtiene los datos de un ingreso específico según su identificador.
     *
     * Lógica:
     * 1. Localiza el ingreso mediante IngresoService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "ingresos" WHERE "id_ingreso" = :id LIMIT 1;
     *
     * @param int|string $id Identificador del ingreso.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $ingreso = IngresoService::getOne($id);
        if (!$ingreso) {
            return $this->errorResponse('Ingreso no encontrado', 404);
        }
        return $this->successResponse($ingreso, 'Ingreso obtenido correctamente');
    }

    /**
     * Actualiza el nombre de un ingreso existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a IngresoService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "ingresos" WHERE "id_ingreso" = :id LIMIT 1;
     * -- UPDATE "ingresos" SET "..." = '...', "last_update" = NOW() WHERE "id_ingreso" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_tipo_orden' => 'nullable|int|exists:tipos_ordenes,id_tipo_orden',
            'id_vehiculo' => 'nullable|int|exists:vehiculos,id_vehiculo',
            'id_admin' => 'nullable|int|exists:admins,id_admin',
            'observaciones_cliente' => 'nullable|string|max:10000',
            'estado' => 'nullable|in:' . implode(',', IngresoEstado::values()),
            'pdf_checklist' => 'nullable|string|max:10000',
            'fecha_anulacion' => 'nullable|date',
            'motivo_anulacion' => 'nullable|string|max:250',
        ]);

        // Validar que al menos un campo sea modificado
        if (!$request->has('id_tipo_orden') && !$request->has('id_vehiculo') && !$request->has('id_admin') && !$request->has('observaciones_cliente') && !$request->has('estado') && !$request->has('pdf_checklist') && !$request->has('fecha_anulacion') && !$request->has('motivo_anulacion')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $ingreso = IngresoService::update($id, $data);
        if (!$ingreso) {
            return $this->errorResponse('Ingreso no encontrado', 404);
        }

        return $this->successResponse($ingreso, 'Ingreso actualizado correctamente');
    }

    /**
     * Elimina un ingreso de la base de datos.
     *
     * Lógica:
     * 1. Invoca IngresoService::delete($id).
     * 2. Si el ingreso no existe o no pudo eliminarse retorna error 404.
     * 3. Retorna el ingreso eliminado en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "ingresos" WHERE "id_ingreso" = :id LIMIT 1;
     * -- DELETE FROM "ingresos" WHERE "id_ingreso" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $ingreso = IngresoService::delete($id);
        if (!$ingreso) {
            return $this->errorResponse('Ingreso no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($ingreso, 'Ingreso eliminado correctamente');
    }

    /**
     * Anula un ingreso de vehiculo.
     *
     * Lógica:
     * 1. Valida que el motivo de anulacion y admin de anulacion sean una cadena obligatoria de hasta 250 caracteres.
     * 2. Llama a IngresoService::update($id, $data) para asignar el estado ANULADO y la fecha de anulacion.
     * 3. Retorna el ingreso anulado con código HTTP 200 (OK).
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "ingresos" WHERE "id_ingreso" = :id LIMIT 1;
     * -- UPDATE "ingresos" SET "estado" = 'anulado', "fecha_anulacion" = NOW(), "id_admin_anulacion" = :id_admin_anulacion, "motivo_anulacion" = :motivo_anulacion WHERE "id_ingreso" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function anular(Request $request, $id): JsonResponse
    {
        $request->validate([
            'motivo_anulacion' => 'required|string|max:250',
        ]);

        $data = $request->all();

        $ingreso = IngresoService::update($id, $data);
        if (!$ingreso) {
            return $this->errorResponse('Ingreso no encontrado', 404);
        }

        return $this->successResponse($ingreso, 'Ingreso anulado correctamente');
    }
}
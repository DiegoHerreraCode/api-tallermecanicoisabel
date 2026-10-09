<?php

namespace App\Http\Controllers;

use App\Services\TipoOrdenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class TipoOrdenController
 *
 * Controlador RESTful encargado de gestionar los tipos de orden de servicio que se manejan en el taller.
 * Expone las operaciones CRUD comunicándose directamente con TipoOrdenService.
 */
class TipoOrdenController extends Controller
{
    /**
     * Retorna todos los tipos de orden de servicio registrados en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de tipos de orden de servicio mediante TipoOrdenService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "tipos_ordenes";
     *
     * @return JsonResponse Lista de tipos de orden de servicio.
     */
    public function index(): JsonResponse
    {
        $tiposOrden = TipoOrdenService::getAll();
        return $this->successResponse(
            $tiposOrden,
            $tiposOrden->isEmpty() ? 'No se encontraron tipos de orden de servicio' : 'Tipos de orden de servicio obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo tipo de orden de servicio.
     *
     * Lógica:
     * 1. Valida que el nombre del tipo de orden de servicio sea una cadena obligatoria de hasta 100 caracteres.
     * 2. Llama a TipoOrdenService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna el tipo de orden de servicio creado con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "tipos_ordenes" ("id_tipo_orden", "nombre", "descripcion", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $data = $request->all();

        $tipoOrden = TipoOrdenService::create($data);
        if (!$tipoOrden) {
            return $this->errorResponse('Tipo de orden de servicio no creado', 404);
        }
        return $this->successResponse($tipoOrden, 'Tipo de orden de servicio creado correctamente', 201);
    }

    /**
     * Obtiene los datos de un tipo de orden de servicio específico según su identificador.
     *
     * Lógica:
     * 1. Localiza el tipo de orden de servicio mediante TipoOrdenService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "tipos_ordenes" WHERE "id_tipo_orden" = :id LIMIT 1;
     *
     * @param int|string $id Identificador del tipo de orden de servicio.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $tipoOrden = TipoOrdenService::getOne($id);
        if (!$tipoOrden) {
            return $this->errorResponse('Tipo de orden de servicio no encontrado', 404);
        }

        return $this->successResponse($tipoOrden, 'Tipo de orden de servicio obtenido correctamente');
    }

    /**
     * Actualiza el nombre de un tipo de orden de servicio existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a TipoOrdenService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "tipos_ordenes" WHERE "id_tipo_orden" = :id LIMIT 1;
     * -- UPDATE "tipos_ordenes" SET "nombre" = '...', "descripcion" = '...', "updated_at" = NOW() WHERE "id_tipo_orden" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nombre' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string|max:500',
        ]);

        // Validar que al menos un campo sea modificado
        if (!$request->has('nombre') && !$request->has('descripcion')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $tipoOrden = TipoOrdenService::update($id, $data);
        if (!$tipoOrden) {
            return $this->errorResponse('Tipo de orden de servicio no encontrado', 404);
        }

        return $this->successResponse($tipoOrden, 'Tipo de orden de servicio actualizado correctamente');
    }

    /**
     * Elimina un tipo de orden de servicio de la base de datos.
     *
     * Lógica:
     * 1. Invoca TipoOrdenService::delete($id).
     * 2. Si el tipo de orden de servicio no existe o no pudo eliminarse retorna error 404.
     * 3. Retorna el tipo de orden de servicio eliminado en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "tipos_ordenes" WHERE "id_tipo_orden" = :id LIMIT 1;
     * -- DELETE FROM "tipos_ordenes" WHERE "id_tipo_orden" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $tipoOrden = TipoOrdenService::delete($id);
        if (!$tipoOrden) {
            return $this->errorResponse('Tipo de orden de servicio no se pudo eliminar', 404);
        }

        return $this->successResponse($tipoOrden, 'Tipo de orden de servicio eliminado correctamente');
    }
}
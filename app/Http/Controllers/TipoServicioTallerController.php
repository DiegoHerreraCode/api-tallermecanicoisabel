<?php

namespace App\Http\Controllers;

use App\Services\TipoServicioTallerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class TipoServicioTallerController
 *
 * Controlador RESTful encargado de gestionar los tipos de servicios que se realizan en el taller.
 * Expone las operaciones CRUD comunicándose directamente con TipoServicioTallerService.
 */
class TipoServicioTallerController extends Controller
{
    /**
     * Retorna todos los tipos de servicio registrados en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de tipos de servicio mediante TipoServicioTallerService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "tipos_servicios_taller";
     *
     * @return JsonResponse Lista de tipos de servicio.
     */
    public function index(): JsonResponse
    {
        $tiposServiciosTaller = TipoServicioTallerService::getAll();
        return $this->successResponse(
            $tiposServiciosTaller,
            $tiposServiciosTaller->isEmpty() ? 'No se encontraron tipos de servicio' : 'Tipos de servicio obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo tipo de servicio.
     *
     * Lógica:
     * 1. Valida que el nombre del tipo de servicio sea una cadena obligatoria de hasta 100 caracteres.
     * 2. Llama a TipoServicioTallerService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna el tipo de servicio creado con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "tipos_servicios_taller" ("id_tipo_servicio_taller", "nombre", "descripcion", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $data = $request->all();

        $tipoServicioTaller = TipoServicioTallerService::create($data);
        if (!$tipoServicioTaller) {
            return $this->errorResponse('Tipo de servicio no creado', 404);
        }
        return $this->successResponse($tipoServicioTaller, 'Tipo de servicio creado correctamente', 201);
    }

    /**
     * Obtiene los datos de un tipo de servicio específico según su identificador.
     *
     * Lógica:
     * 1. Localiza el tipo de servicio mediante TipoServicioTallerService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "tipos_servicios_taller" WHERE "id_tipo_servicio_taller" = :id LIMIT 1;
     *
     * @param int|string $id Identificador del tipo de servicio.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $tipoServicioTaller = TipoServicioTallerService::getOne($id);
        if (!$tipoServicioTaller) {
            return $this->errorResponse('Tipo de servicio no encontrado', 404);
        }

        return $this->successResponse($tipoServicioTaller, 'Tipo de servicio obtenido correctamente');
    }

    /**
     * Actualiza el nombre de un tipo de servicio existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a TipoServicioTallerService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "tipos_servicios_taller" WHERE "id_tipo_servicio_taller" = :id LIMIT 1;
     * -- UPDATE "tipos_servicios_taller" SET "nombre" = '...', "descripcion" = '...', "updated_at" = NOW() WHERE "id_tipo_servicio_taller" = :id;
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

        $tipoServicioTaller = TipoServicioTallerService::update($id, $data);
        if (!$tipoServicioTaller) {
            return $this->errorResponse('Tipo de servicio no encontrado', 404);
        }

        return $this->successResponse($tipoServicioTaller, 'Tipo de servicio actualizado correctamente');
    }

    /**
     * Elimina un tipo de servicio de la base de datos.
     *
     * Lógica:
     * 1. Invoca TipoServicioTallerService::delete($id).
     * 2. Si el tipo de servicio no existe o no pudo eliminarse retorna error 404.
     * 3. Retorna el tipo de servicio eliminado en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "tipos_servicios_taller" WHERE "id_tipo_servicio_taller" = :id LIMIT 1;
     * -- DELETE FROM "tipos_servicios_taller" WHERE "id_tipo_servicio_taller" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $tipoServicioTaller = TipoServicioTallerService::delete($id);
        if (!$tipoServicioTaller) {
            return $this->errorResponse('Tipo de servicio no se pudo eliminar', 404);
        }

        return $this->successResponse($tipoServicioTaller, 'Tipo de servicio eliminado correctamente');
    }
}
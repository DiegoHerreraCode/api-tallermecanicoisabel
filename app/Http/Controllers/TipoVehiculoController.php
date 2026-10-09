<?php

namespace App\Http\Controllers;

use App\Services\TipoVehiculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class TipoVehiculoController
 *
 * Controlador RESTful encargado de gestionar los tipos de vehículos atendidos en el taller.
 * Expone las operaciones CRUD comunicándose directamente con TipoVehiculoService.
 */
class TipoVehiculoController extends Controller
{
    /**
     * Retorna todos los tipos de vehiculos registrados en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de tipos mediante TipoVehiculoService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "tipos_vehiculos";
     *
     * @return JsonResponse Lista de tipos de vehiculos.
     */
    public function index(): JsonResponse
    {
        $tipos = TipoVehiculoService::getAll();
        return $this->successResponse(
            $tipos,
            $tipos->isEmpty() ? 'No se encontraron tipos de vehiculos' : 'Tipos de vehiculos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo tipo de vehiculo.
     *
     * Lógica:
     * 1. Valida que el nombre del tipo de vehiculo sea una cadena obligatoria de hasta 100 caracteres.
     * 2. Llama a TipoVehiculoService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna el tipo de vehiculo creado con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "tipos_vehiculos" ("id_tipo_vehiculo", "nombre", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $data = $request->all();

        $tipo = TipoVehiculoService::create($data);
        if (!$tipo) {
            return $this->errorResponse('Tipo de vehiculo no encontrado', 404);
        }
        return $this->successResponse($tipo, 'Tipo de vehiculo creado correctamente', 201);
    }

    /**
     * Obtiene los datos de un tipo de vehiculo específico según su identificador.
     *
     * Lógica:
     * 1. Localiza el tipo de vehiculo mediante TipoVehiculoService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "tipos_vehiculos" WHERE "id_tipo_vehiculo" = :id LIMIT 1;
     *
     * @param int|string $id Identificador del tipo de vehiculo.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $tipo = TipoVehiculoService::getOne($id);
        if (!$tipo) {
            return $this->errorResponse('Tipo de vehiculo no encontrado', 404);
        }

        return $this->successResponse($tipo, 'Tipo de vehiculo obtenido correctamente');
    }

    /**
     * Actualiza el nombre de un tipo de vehiculo existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a TipoVehiculoService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "tipos_vehiculos" WHERE "id_tipo_vehiculo" = :id LIMIT 1;
     * -- UPDATE "tipos_vehiculos" SET "nombre" = '...', "updated_at" = NOW() WHERE "id_tipo_vehiculo" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nombre' => 'string|max:100',
        ]);

        // Validar que al menos un campo sea modificado
        if (!$request->has('nombre')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $tipo = TipoVehiculoService::update($id, $data);
        if (!$tipo) {
            return $this->errorResponse('Tipo de vehiculo no encontrado', 404);
        }

        return $this->successResponse($tipo, 'Tipo de vehiculo actualizado correctamente');
    }

    /**
     * Elimina un tipo de vehiculo de la base de datos.
     *
     * Lógica:
     * 1. Invoca TipoVehiculoService::delete($id).
     * 2. Si el tipo de vehiculo no existe o no pudo eliminarse retorna error 404.
     * 3. Retorna el tipo de vehiculo eliminado en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "tipos_vehiculos" WHERE "id_tipo_vehiculo" = :id LIMIT 1;
     * -- DELETE FROM "tipos_vehiculos" WHERE "id_tipo_vehiculo" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $tipo = TipoVehiculoService::delete($id);
        if (!$tipo) {
            return $this->errorResponse('Tipo de vehiculo no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($tipo, 'Tipo de vehiculo eliminado correctamente');
    }
}
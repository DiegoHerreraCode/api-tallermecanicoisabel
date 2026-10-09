<?php

namespace App\Http\Controllers;

use App\Services\ModeloVehiculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ModeloVehiculoController
 *
 * Controlador RESTful encargado de gestionar las modelos de vehículos atendidos en el taller.
 * Expone las operaciones CRUD comunicándose directamente con ModeloVehiculoService.
 */
class ModeloVehiculoController extends Controller
{
    /**
     * Retorna todas las modelos registrados en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de modelos mediante ModeloVehiculoService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "modelos_vehiculos";
     *
     * @return JsonResponse Lista de modelos.
     */
    public function index(): JsonResponse
    {
        $marcas = ModeloVehiculoService::getAll();
        return $this->successResponse(
            $marcas,
            $marcas->isEmpty() ? 'No se encontraron modelos de vehiculos' : 'Modelos de vehiculos obtenidos correctamente'
        );
    }

    /**
     * Registra una nueva modelo de vehiculo.
     *
     * Lógica:
     * 1. Valida que el nombre de la modelo sea una cadena obligatoria de hasta 100 caracteres.
     * 2. Llama a ModeloVehiculoService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna la modelo creada con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "modelos_vehiculos" ("id_modelo_vehiculo", "nombre", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'id_marca_vehiculo' => 'required|integer|exists:marcas_vehiculos,id_marca_vehiculo',
        ]);

        $data = $request->all();

        $modelo = ModeloVehiculoService::create($data);
        if (!$modelo) {
            return $this->errorResponse('Modelo no encontrado', 404);
        }
        return $this->successResponse($modelo, 'Modelo creado correctamente', 201);
    }

    /**
     * Obtiene los datos de una marca de vehiculo específica según su identificador.
     *
     * Lógica:
     * 1. Localiza la marca mediante MarcaVehiculoService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "marcas_vehiculos" WHERE "id_marca_vehiculo" = :id LIMIT 1;
     *
     * @param int|string $id Identificador de la marca.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $modelo = ModeloVehiculoService::getOne($id);
        if (!$modelo) {
            return $this->errorResponse('Modelo no encontrado', 404);
        }

        return $this->successResponse($modelo, 'Modelo de vehiculo obtenido correctamente');
    }

    /**
     * Actualiza el nombre de una modelo de vehiculo existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a ModeloVehiculoService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "modelos_vehiculos" WHERE "id_modelo_vehiculo" = :id LIMIT 1;
     * -- UPDATE "modelos_vehiculos" SET "nombre" = '...', "updated_at" = NOW() WHERE "id_modelo_vehiculo" = :id;
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

        $modelo = ModeloVehiculoService::update($id, $data);
        if (!$modelo) {
            return $this->errorResponse('Modelo no encontrado', 404);
        }

        return $this->successResponse($modelo, 'Modelo de vehiculo actualizado correctamente');
    }

    /**
     * Elimina una modelo de vehiculo de la base de datos.
     *
     * Lógica:
     * 1. Invoca ModeloVehiculoService::delete($id).
     * 2. Si la modelo no existe o no pudo eliminarse retorna error 404.
     * 3. Retorna la modelo eliminada en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "marcas_vehiculos" WHERE "id_marca_vehiculo" = :id LIMIT 1;
     * -- DELETE FROM "marcas_vehiculos" WHERE "id_marca_vehiculo" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $modelo = ModeloVehiculoService::delete($id);
        if (!$modelo) {
            return $this->errorResponse('Modelo de vehiculo no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($modelo, 'Modelo de vehiculo eliminado correctamente');
    }
}
<?php

namespace App\Http\Controllers;

use App\Services\VariableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class VariableController
 *
 * Controlador RESTful encargado de gestionar las variables del sistema.
 * Expone las operaciones CRUD comunicándose directamente con VariableService.
 */
class VariableController extends Controller
{
    /**
     * Retorna todas las variables del sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de variables mediante VariableService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "variables";
     *
     * @return JsonResponse Lista de variables.
     */
    public function index(): JsonResponse
    {
        $variables = VariableService::getAll();
        return $this->successResponse(
            $variables,
            $variables->isEmpty() ? 'No se encontraron variables' : 'Variables obtenidas correctamente'
        );
    }

    /**
     * Registra una nueva variable del sistema.
     *
     * Lógica:
     * 1. Valida que el nombre de la variable sea una cadena obligatoria de hasta 150 caracteres.
     * 2. Valida que el valor de la variable sea una cadena obligatoria de hasta 150 caracteres.
     * 3. Valida que el tipo de la variable sea una cadena obligatoria de hasta 150 caracteres.
     * 4. Llama a VariableService::create($data) para asignar ID e insertar en la base de datos.
     * 5. Retorna la variable creada con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "variables" ("id_variable", "nombre", "valor", "tipo", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'valor' => 'required|string|max:150',
            'tipo' => 'required|string|max:150',
        ]);

        $data = $request->all();

        $variable = VariableService::create($data);
        if (!$variable) {
            return $this->errorResponse('Variable no creada', 404);
        }
        return $this->successResponse($variable, 'Variable creada correctamente', 201);
    }

    /**
     * Obtiene los datos de una variable específica según su identificador.
     *
     * Lógica:
     * 1. Localiza la variable mediante VariableService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "variables" WHERE "id_variable" = :id LIMIT 1;
     *
     * @param int|string $id Identificador de la variable.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $variable = VariableService::getOne($id);
        if (!$variable) {
            return $this->errorResponse('Variable no encontrada', 404);
        }

        return $this->successResponse($variable, 'Variable obtenida correctamente');
    }

    /**
     * Actualiza una variable existente.
     *
     * Lógica:
     * 1. Valida los campos 'nombre', 'valor', 'tipo' recibidos.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a VariableService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "variables" WHERE "id_variable" = :id LIMIT 1;
     * -- UPDATE "variables" SET "nombre" = '...', "valor" = '...', "tipo" = '...', "updated_at" = NOW() WHERE "id_variable" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nombre' => 'nullable|string|max:150',
            'valor' => 'nullable|string|max:150',
            'tipo' => 'nullable|string|max:150',
        ]);

        // Validar que al menos un campo sea modificado
        if (!$request->has('nombre') && !$request->has('valor') && !$request->has('tipo')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $variable = VariableService::update($id, $data);
        if (!$variable) {
            return $this->errorResponse('Variable no encontrada', 404);
        }

        return $this->successResponse($variable, 'Variable actualizada correctamente');
    }

    /**
     * Elimina una variable del sistema.
     *
     * Lógica:
     * 1. Invoca VariableService::delete($id).
     * 2. Si la variable no existe o no pudo eliminarse retorna error 404.
     * 3. Retorna la variable eliminada en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "variables" WHERE "id_variable" = :id LIMIT 1;
     * -- DELETE FROM "variables" WHERE "id_variable" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $variable = VariableService::delete($id);
        if (!$variable) {
            return $this->errorResponse('Variable no encontrada o no se pudo eliminar', 404);
        }

        return $this->successResponse($variable, 'Variable eliminada correctamente');
    }
}
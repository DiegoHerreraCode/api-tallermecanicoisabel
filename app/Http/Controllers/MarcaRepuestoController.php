<?php

namespace App\Http\Controllers;

use App\Services\MarcaRepuestoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class MarcaRepuestoController
 *
 * Controlador RESTful encargado de gestionar las marcas de repuestos.
 * Expone las operaciones CRUD comunicándose directamente con MarcaRepuestoService.
 */
class MarcaRepuestoController extends Controller
{
    /**
     * Retorna todas las marcas registradas en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de marcas mediante MarcaRepuestoService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "marcas_repuestos";
     *
     * @return JsonResponse Lista de marcas.
     */
    public function index(): JsonResponse
    {
        $marcas = MarcaRepuestoService::getAll();
        return $this->successResponse(
            $marcas,
            $marcas->isEmpty() ? 'No se encontraron marcas de repuestos' : 'Marcas de repuestos obtenidas correctamente'
        );
    }

    /**
     * Registra una nueva marca de repuesto.
     *
     * Lógica:
     * 1. Valida que el nombre de la marca sea una cadena obligatoria de hasta 100 caracteres.
     * 2. Llama a MarcaRepuestoService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna la marca creada con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "marcas_repuestos" ("id_marca_repuesto", "nombre", "created_at", "updated_at") VALUES (...);
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

        $marca = MarcaRepuestoService::create($data);
        if (!$marca) {
            return $this->errorResponse('Marca no creada', 404);
        }
        return $this->successResponse($marca, 'Marca creada correctamente', 201);
    }

    /**
     * Obtiene los datos de una marca de repuesto específica según su identificador.
     *
     * Lógica:
     * 1. Localiza la marca mediante MarcaRepuestoService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "marcas_repuestos" WHERE "id_marca_repuesto" = :id LIMIT 1;
     *
     * @param int|string $id Identificador de la marca.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $marca = MarcaRepuestoService::getOne($id);
        if (!$marca) {
            return $this->errorResponse('Marca no encontrada', 404);
        }

        return $this->successResponse($marca, 'Marca de repuesto obtenida correctamente');
    }

    /**
     * Actualiza el nombre de una marca de repuesto existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a MarcaRepuestoService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "marcas_repuestos" WHERE "id_marca_repuesto" = :id LIMIT 1;
     * -- UPDATE "marcas_repuestos" SET "nombre" = '...', "updated_at" = NOW() WHERE "id_marca_repuesto" = :id;
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

        $marca = MarcaRepuestoService::update($id, $data);
        if (!$marca) {
            return $this->errorResponse('Marca no encontrada', 404);
        }

        return $this->successResponse($marca, 'Marca de repuesto actualizada correctamente');
    }

    /**
     * Elimina una marca de repuesto de la base de datos.
     *
     * Lógica:
     * 1. Invoca MarcaRepuestoService::delete($id).
     * 2. Si la marca no existe o no pudo eliminarse retorna error 404.
     * 3. Retorna la marca eliminada en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "marcas_repuestos" WHERE "id_marca_repuesto" = :id LIMIT 1;
     * -- DELETE FROM "marcas_repuestos" WHERE "id_marca_repuesto" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $marca = MarcaRepuestoService::delete($id);
        if (!$marca) {
            return $this->errorResponse('Marca de repuesto no encontrada o no se pudo eliminar', 404);
        }

        return $this->successResponse($marca, 'Marca de repuesto eliminada correctamente');
    }
}
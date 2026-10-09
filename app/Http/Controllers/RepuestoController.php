<?php

namespace App\Http\Controllers;

use App\Services\RepuestoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class RepuestoController
 *
 * Controlador RESTful encargado de gestionar las repuestos utilizados en taller.
 * Expone las operaciones CRUD comunicándose directamente con RepuestoService.
 */
class RepuestoController extends Controller
{
    /**
     * Retorna todas las modelos registrados en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de modelos mediante RepuestoService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "repuestos";
     *
     * @return JsonResponse Lista de repuestos.
     */
    public function index(): JsonResponse
    {
        $marcas = RepuestoService::getAll();
        return $this->successResponse(
            $marcas,
            $marcas->isEmpty() ? 'No se encontraron repuestos' : 'Repuestos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo repuesto.
     *
     * Lógica:
     * 1. Valida que el nombre del modelo del repuesto sea una cadena obligatoria de hasta 250 caracteres.
     * 2. Llama a RepuestoService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna el repuesto creado con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "repuestos" ("id_marca_repuesto", "nombre_modelo", "ultimo_costo_compra_unitario", "monto_venta", "utilidad", "porcentaje_ganancia", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_marca_repuesto' => 'required|integer|exists:marcas_repuestos,id_marca_repuesto',
            'nombre_modelo' => 'required|string|max:250',
            'ultimo_costo_compra_unitario' => 'required|numeric',
            'monto_venta' => 'required|numeric',
            'utilidad' => 'required|numeric',
            'porcentaje_ganancia' => 'required|numeric',
        ]);

        $data = $request->all();

        $repuesto = RepuestoService::create($data);
        if (!$repuesto) {
            return $this->errorResponse('Repuesto no creado', 404);
        }
        return $this->successResponse($repuesto, 'Repuesto creado correctamente', 201);
    }

    /**
     * Obtiene los datos de un repuesto específico según su identificador.
     *
     * Lógica:
     * 1. Localiza el repuesto mediante RepuestoService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "repuestos" WHERE "id_repuesto" = :id LIMIT 1;
     *
     * @param int|string $id Identificador del repuesto.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $repuesto = RepuestoService::getOne($id);
        if (!$repuesto) {
            return $this->errorResponse('Repuesto no encontrado', 404);
        }

        return $this->successResponse($repuesto, 'Repuesto obtenido correctamente');
    }

    /**
     * Actualiza la data de un repuesto existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a RepuestoService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "repuestos" WHERE "id_repuesto" = :id LIMIT 1;
     * -- UPDATE "repuestos" SET "nombre_modelo" = '...', "updated_at" = NOW() WHERE "id_repuesto" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_marca_repuesto' => 'nullable|integer|exists:marcas_repuestos,id_marca_repuesto',
            'nombre_modelo' => 'nullable|string|max:250',
            'ultimo_costo_compra_unitario' => 'nullable|numeric',
            'monto_venta' => 'nullable|numeric',
            'utilidad' => 'nullable|numeric',
            'porcentaje_ganancia' => 'nullable|numeric',
        ]);

        // Validar que al menos un campo sea modificado
        if (!$request->has('nombre_modelo') && !$request->has('id_marca_repuesto') && !$request->has('ultimo_costo_compra_unitario') && !$request->has('monto_venta') && !$request->has('utilidad') && !$request->has('porcentaje_ganancia')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $repuesto = RepuestoService::update($id, $data);
        if (!$repuesto) {
            return $this->errorResponse('Repuesto no encontrado', 404);
        }

        return $this->successResponse($repuesto, 'Repuesto actualizado correctamente');
    }

    /**
     * Elimina un repuesto de la base de datos.
     *
     * Lógica:
     * 1. Invoca RepuestoService::delete($id).
     * 2. Si el repuesto no se pudo eliminar retorna error 404.
     * 3. Retorna el repuesto eliminado en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "repuestos" WHERE "id_repuesto" = :id LIMIT 1;
     * -- DELETE FROM "repuestos" WHERE "id_repuesto" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $repuesto = RepuestoService::delete($id);
        if (!$repuesto) {
            return $this->errorResponse('Repuesto no se pudo eliminar', 404);
        }

        return $this->successResponse($repuesto, 'Repuesto eliminado correctamente');
    }
}
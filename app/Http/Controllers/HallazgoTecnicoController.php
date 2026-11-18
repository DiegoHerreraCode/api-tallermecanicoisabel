<?php

namespace App\Http\Controllers;

use App\Services\HallazgoTecnicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class HallazgoTecnicoController
 *
 * Controlador RESTful encargado de gestionar los hallazgos tecnicos realizados en taller.
 * Expone las operaciones CRUD comunicándose directamente con HallazgoTecnicoService.
 */
class HallazgoTecnicoController extends Controller
{
    /**
     * Retorna todas las hallazgos tecnicos registrados en el sistema con opción de filtrado.
     *
     * @param Request $request
     * @return JsonResponse Lista de hallazgos tecnicos.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'id_orden' => $request->query('id_orden'),
            'id_ingreso' => $request->query('id_ingreso'),
        ];
        $hallazgosTecnicos = HallazgoTecnicoService::getAll($filters);
        return $this->successResponse(
            $hallazgosTecnicos,
            $hallazgosTecnicos->isEmpty() ? 'No se encontraron hallazgos tecnicos' : 'Hallazgos tecnicos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo hallazgo tecnico (asociado a una orden o a un ingreso).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_orden' => 'nullable|integer|exists:ordenes,id_orden',
            'id_ingreso' => 'nullable|integer|exists:ingresos,id_ingreso',
            'hallazgo' => 'required|string|max:1000',
            'detalle' => 'nullable|string|max:10000',
        ]);

        if (!$request->filled('id_orden') && !$request->filled('id_ingreso')) {
            return $this->errorResponse('Debe asociar el hallazgo a una orden o a un ingreso', 422);
        }

        $data = $request->all();

        $hallazgoTecnico = HallazgoTecnicoService::create($data);
        if (!$hallazgoTecnico) {
            return $this->errorResponse('No se pudo registrar el hallazgo técnico. Verifique que la orden o el ingreso estén activos.', 422);
        }
        return $this->successResponse($hallazgoTecnico, 'Hallazgo técnico creado correctamente', 201);
    }

    /**
     * Obtiene los datos de un hallazgo tecnico específico según su identificador.
     *
     * Lógica:
     * 1. Localiza el hallazgo tecnico mediante HallazgoTecnicoService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "hallazgos_tecnicos" WHERE "id_hallazgo_tecnico" = :id LIMIT 1;
     *
     * @param int|string $id Identificador del hallazgo tecnico.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $hallazgoTecnico = HallazgoTecnicoService::getOne($id);
        if (!$hallazgoTecnico) {
            return $this->errorResponse('Hallazgo tecnico no encontrado', 404);
        }

        return $this->successResponse($hallazgoTecnico, 'Hallazgo tecnico obtenido correctamente');
    }

    /**
     * Actualiza la data de un hallazgo tecnico existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a HallazgoTecnicoService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "hallazgos_tecnicos" WHERE "id_hallazgo_tecnico" = :id LIMIT 1;
     * -- UPDATE "hallazgos_tecnicos" SET "id_orden" = '...', "hallazgo" = '...', "detalle" = '...', "updated_at" = NOW() WHERE "id_hallazgo_tecnico" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_orden' => 'nullable|integer|exists:ordenes,id_orden',
            'id_ingreso' => 'nullable|integer|exists:ingresos,id_ingreso',
            'hallazgo' => 'nullable|string|max:1000',
            'detalle' => 'nullable|string|max:10000',
        ]);

        // Validar que al menos un campo sea modificado
        if (!$request->has('id_orden') && !$request->has('id_ingreso') && !$request->has('hallazgo') && !$request->has('detalle')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $hallazgoTecnico = HallazgoTecnicoService::update($id, $data);
        if (!$hallazgoTecnico) {
            return $this->errorResponse('No se pudo actualizar el hallazgo técnico', 422);
        }

        return $this->successResponse($hallazgoTecnico, 'Hallazgo técnico actualizado correctamente');
    }

    /**
     * Elimina un hallazgo tecnico de la base de datos.
     *
     * Lógica:
     * 1. Invoca HallazgoTecnicoService::delete($id).
     * 2. Si el hallazgo tecnico no se pudo eliminar retorna error 404.
     * 3. Retorna el hallazgo tecnico eliminado en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "hallazgos_tecnicos" WHERE "id_hallazgo_tecnico" = :id LIMIT 1;
     * -- DELETE FROM "hallazgos_tecnicos" WHERE "id_hallazgo_tecnico" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $hallazgoTecnico = HallazgoTecnicoService::delete($id);
        if (!$hallazgoTecnico) {
            return $this->errorResponse('Hallazgo tecnico no se pudo eliminar', 404);
        }

        return $this->successResponse($hallazgoTecnico, 'Hallazgo tecnico eliminado correctamente');
    }
}
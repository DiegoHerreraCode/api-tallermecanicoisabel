<?php

namespace App\Http\Controllers;

use App\Services\ServicioTallerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ServicioTallerController
 *
 * Controlador RESTful encargado de gestionar los servicios utilizados en taller.
 * Expone las operaciones CRUD comunicándose directamente con ServicioTallerService.
 */
class ServicioTallerController extends Controller
{
    /**
     * Retorna todas las modelos registrados en el sistema.
     *
     * Lógica:
     * 1. Consulta la lista total de modelos mediante ServicioTallerService::getAll().
     * 2. Devuelve respuesta JSON con status 200 y mensaje representativo.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "servicios_taller";
     *
     * @return JsonResponse Lista de servicios de taller.
     */
    public function index(): JsonResponse
    {
        $serviciosTaller = ServicioTallerService::getAll();
        return $this->successResponse(
            $serviciosTaller,
            $serviciosTaller->isEmpty() ? 'No se encontraron servicios de taller' : 'Servicios de taller obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo servicio de taller.
     *
     * Lógica:
     * 1. Valida que el nombre del modelo del servicio de taller sea una cadena obligatoria de hasta 250 caracteres.
     * 2. Llama a ServicioTallerService::create($data) para asignar ID e insertar en la base de datos.
     * 3. Retorna el servicio de taller creado con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- INSERT INTO "servicios_taller" ("id_tipo_servicio_taller", "nombre", "descripcion", "costo", "monto_venta", "utilidad", "porcentaje_ganancia", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request Petición con el campo 'nombre'.
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_tipo_servicio_taller' => 'required|integer|exists:tipos_servicios_taller,id_tipo_servicio_taller',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:500',
            'costo' => 'required|numeric',
            'monto_venta' => 'required|numeric',
            'utilidad' => 'required|numeric',
            'porcentaje_ganancia' => 'required|numeric',
        ]);

        $data = $request->all();

        $servicioTaller = ServicioTallerService::create($data);
        if (!$servicioTaller) {
            return $this->errorResponse('Servicio de taller no creado', 404);
        }
        return $this->successResponse($servicioTaller, 'Servicio de taller creado correctamente', 201);
    }

    /**
     * Obtiene los datos de un servicio de taller específico según su identificador.
     *
     * Lógica:
     * 1. Localiza el servicio de taller mediante ServicioTallerService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario retorna el objeto con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "servicios_taller" WHERE "id_servicio_taller" = :id LIMIT 1;
     *
     * @param int|string $id Identificador del servicio de taller.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $servicioTaller = ServicioTallerService::getOne($id);
        if (!$servicioTaller) {
            return $this->errorResponse('Servicio de taller no encontrado', 404);
        }

        return $this->successResponse($servicioTaller, 'Servicio de taller obtenido correctamente');
    }

    /**
     * Actualiza la data de un servicio de taller existente.
     *
     * Lógica:
     * 1. Valida el campo 'nombre' recibido.
     * 2. Comprueba que el usuario haya enviado al menos un campo en el request.
     * 3. Llama a ServicioTallerService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "servicios_taller" WHERE "id_servicio_taller" = :id LIMIT 1;
     * -- UPDATE "servicios_taller" SET "nombre" = '...', "updated_at" = NOW() WHERE "id_servicio_taller" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_tipo_servicio_taller' => 'nullable|integer|exists:tipos_servicios_taller,id_tipo_servicio_taller',
            'nombre' => 'nullable|string|max:200',
            'descripcion' => 'nullable|string|max:500',
            'costo' => 'nullable|numeric',
            'monto_venta' => 'nullable|numeric',
            'utilidad' => 'nullable|numeric',
            'porcentaje_ganancia' => 'nullable|numeric',
            'is_deleted' => 'nullable|boolean',
        ]);

        // Validar que al menos un campo sea modificado
        if (!$request->has('id_tipo_servicio_taller') && !$request->has('nombre') && !$request->has('descripcion') && !$request->has('costo') && !$request->has('monto_venta') && !$request->has('utilidad') && !$request->has('porcentaje_ganancia') && !$request->has('is_deleted')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $servicioTaller = ServicioTallerService::update($id, $data);
        if (!$servicioTaller) {
            return $this->errorResponse('Servicio de taller no encontrado', 404);
        }

        return $this->successResponse($servicioTaller, 'Servicio de taller actualizado correctamente');
    }

    /**
     * Elimina un servicio de taller de la base de datos.
     *
     * Lógica:
     * 1. Invoca ServicioTallerService::delete($id).
     * 2. Si el servicio de taller no se pudo eliminar retorna error 404.
     * 3. Retorna el servicio de taller eliminado en la respuesta.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "servicios_taller" WHERE "id_servicio_taller" = :id LIMIT 1;
     * -- DELETE FROM "servicios_taller" WHERE "id_servicio_taller" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $servicioTaller = ServicioTallerService::delete($id);
        if (!$servicioTaller) {
            return $this->errorResponse('Servicio de taller no se pudo eliminar', 404);
        }

        return $this->successResponse($servicioTaller, 'Servicio de taller eliminado correctamente');
    }
}
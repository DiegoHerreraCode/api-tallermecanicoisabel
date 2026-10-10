<?php

namespace App\Http\Controllers;

use App\Services\OrdenServicioTallerService;
use App\Enums\OrdenServicioTallerEstado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

/**
 * Class OrdenServicioTallerController
 *
 * Controlador RESTful encargado de gestionar los servicios de taller asociados a órdenes de trabajo.
 */
class OrdenServicioTallerController extends Controller
{
    /**
     * Retorna todos los servicios asociados a órdenes.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('id_orden')) {
            $servicios = OrdenServicioTallerService::getByOrden($request->query('id_orden'));
        } else {
            $servicios = OrdenServicioTallerService::getAll();
        }

        return $this->successResponse(
            $servicios,
            $servicios->isEmpty() ? 'No se encontraron servicios de órdenes' : 'Servicios obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo servicio en una orden.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_orden' => 'required|exists:ordenes,id_orden',
            'id_servicio_taller' => 'required|exists:servicios_taller,id_servicio_taller',
            'id_personal' => 'nullable|exists:personal,id_personal',
            'precio_unitario' => 'required|numeric|min:0',
            'cantidad' => 'required|numeric|min:0',
            'monto_total_linea' => 'required|numeric|min:0',
            'estado' => 'nullable|in:' . implode(',', OrdenServicioTallerEstado::values()),
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $item = OrdenServicioTallerService::create($request->all());
        if (!$item) {
            return $this->errorResponse('No se pudo registrar el servicio en la orden', 400);
        }

        return $this->successResponse($item, 'Servicio registrado en la orden correctamente', 201);
    }

    /**
     * Obtiene los datos de un detalle específico de servicio de orden.
     */
    public function show($id): JsonResponse
    {
        $item = OrdenServicioTallerService::getOne($id);
        if (!$item) {
            return $this->errorResponse('Registro de servicio en la orden no encontrado', 404);
        }

        return $this->successResponse($item, 'Servicio de orden obtenido correctamente');
    }

    /**
     * Actualiza los datos o estado de un servicio en una orden.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_servicio_taller' => 'nullable|exists:servicios_taller,id_servicio_taller',
            'id_personal' => 'nullable|exists:personal,id_personal',
            'precio_unitario' => 'nullable|numeric|min:0',
            'cantidad' => 'nullable|numeric|min:0',
            'monto_total_linea' => 'nullable|numeric|min:0',
            'estado' => 'nullable|in:' . implode(',', OrdenServicioTallerEstado::values()),
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $item = OrdenServicioTallerService::update($id, $request->all());
        if (!$item) {
            return $this->errorResponse('Registro de servicio en la orden no encontrado', 404);
        }

        return $this->successResponse($item, 'Servicio de orden actualizado correctamente');
    }

    /**
     * Elimina un servicio de una orden.
     * Solo se permite si su estado es 'pendiente_asignacion' o 'en_espera'.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $item = OrdenServicioTallerService::delete($id);
            if (!$item) {
                return $this->errorResponse('Registro no encontrado o no se pudo eliminar', 404);
            }

            return $this->successResponse($item, 'Servicio eliminado de la orden correctamente');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}

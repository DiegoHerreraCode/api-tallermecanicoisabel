<?php

namespace App\Http\Controllers;

use App\Services\PresupuestoServicioTallerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class PresupuestoServicioTallerController
 *
 * Controlador RESTful encargado de gestionar los servicios asociados a presupuestos.
 */
class PresupuestoServicioTallerController extends Controller
{
    /**
     * Retorna todos los servicios asociados a presupuestos.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('id_presupuesto')) {
            $servicios = PresupuestoServicioTallerService::getByPresupuesto($request->query('id_presupuesto'));
        } else {
            $servicios = PresupuestoServicioTallerService::getAll();
        }

        return $this->successResponse(
            $servicios,
            $servicios->isEmpty() ? 'No se encontraron servicios de presupuestos' : 'Servicios obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo servicio en un presupuesto.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_presupuesto' => 'required|exists:presupuestos,id_presupuesto',
            'id_servicio_taller' => 'required|exists:servicios_taller,id_servicio_taller',
            'costo_unitario_base' => 'nullable|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'porcentaje_ganancia' => 'nullable|numeric',
            'cantidad' => 'required|numeric|min:0',
            'monto_total_linea_base' => 'nullable|numeric|min:0',
            'monto_total_linea' => 'required|numeric|min:0',
        ]);

        $item = PresupuestoServicioTallerService::create($request->all());
        if (!$item) {
            return $this->errorResponse('No se pudo registrar el servicio en el presupuesto', 400);
        }

        return $this->successResponse($item, 'Servicio registrado en el presupuesto correctamente', 201);
    }

    /**
     * Obtiene los datos de un detalle específico de servicio de presupuesto.
     */
    public function show($id): JsonResponse
    {
        $item = PresupuestoServicioTallerService::getOne($id);
        if (!$item) {
            return $this->errorResponse('Registro no encontrado', 404);
        }

        return $this->successResponse($item, 'Servicio de presupuesto obtenido correctamente');
    }

    /**
     * Actualiza los datos de un servicio en un presupuesto.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_servicio_taller' => 'nullable|exists:servicios_taller,id_servicio_taller',
            'costo_unitario_base' => 'nullable|numeric|min:0',
            'precio_unitario' => 'nullable|numeric|min:0',
            'porcentaje_ganancia' => 'nullable|numeric',
            'cantidad' => 'nullable|numeric|min:0',
            'monto_total_linea_base' => 'nullable|numeric|min:0',
            'monto_total_linea' => 'nullable|numeric|min:0',
        ]);

        $item = PresupuestoServicioTallerService::update($id, $request->all());
        if (!$item) {
            return $this->errorResponse('Registro no encontrado', 404);
        }

        return $this->successResponse($item, 'Servicio de presupuesto actualizado correctamente');
    }

    /**
     * Elimina un servicio de un presupuesto.
     */
    public function destroy($id): JsonResponse
    {
        $item = PresupuestoServicioTallerService::delete($id);
        if (!$item) {
            return $this->errorResponse('Registro no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($item, 'Servicio eliminado del presupuesto correctamente');
    }
}

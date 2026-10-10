<?php

namespace App\Http\Controllers;

use App\Services\OrdenRepuestoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class OrdenRepuestoController
 *
 * Controlador RESTful encargado de gestionar los repuestos asociados a órdenes de trabajo.
 */
class OrdenRepuestoController extends Controller
{
    /**
     * Retorna todos los repuestos asociados a órdenes.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('id_orden')) {
            $repuestos = OrdenRepuestoService::getByOrden($request->query('id_orden'));
        } else {
            $repuestos = OrdenRepuestoService::getAll();
        }

        return $this->successResponse(
            $repuestos,
            $repuestos->isEmpty() ? 'No se encontraron repuestos de órdenes' : 'Repuestos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo repuesto en una orden.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_orden' => 'required|exists:ordenes,id_orden',
            'id_repuesto' => 'required|exists:repuestos,id_repuesto',
            'precio_unitario' => 'required|numeric|min:0',
            'cantidad' => 'required|numeric|min:0',
            'monto_total_linea' => 'required|numeric|min:0',
        ]);

        $item = OrdenRepuestoService::create($request->all());
        if (!$item) {
            return $this->errorResponse('No se pudo registrar el repuesto en la orden', 400);
        }

        return $this->successResponse($item, 'Repuesto registrado en la orden correctamente', 201);
    }

    /**
     * Obtiene los datos de un detalle específico de repuesto de orden.
     */
    public function show($id): JsonResponse
    {
        $item = OrdenRepuestoService::getOne($id);
        if (!$item) {
            return $this->errorResponse('Registro de repuesto en la orden no encontrado', 404);
        }

        return $this->successResponse($item, 'Repuesto de orden obtenido correctamente');
    }

    /**
     * Actualiza los datos de un repuesto en una orden.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_repuesto' => 'nullable|exists:repuestos,id_repuesto',
            'precio_unitario' => 'nullable|numeric|min:0',
            'cantidad' => 'nullable|numeric|min:0',
            'monto_total_linea' => 'nullable|numeric|min:0',
        ]);

        $item = OrdenRepuestoService::update($id, $request->all());
        if (!$item) {
            return $this->errorResponse('Registro de repuesto en la orden no encontrado', 404);
        }

        return $this->successResponse($item, 'Repuesto de orden actualizado correctamente');
    }

    /**
     * Elimina un repuesto de una orden.
     */
    public function destroy($id): JsonResponse
    {
        $item = OrdenRepuestoService::delete($id);
        if (!$item) {
            return $this->errorResponse('Registro no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($item, 'Repuesto eliminado de la orden correctamente');
    }
}

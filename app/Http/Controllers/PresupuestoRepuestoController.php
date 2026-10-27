<?php

namespace App\Http\Controllers;

use App\Services\PresupuestoRepuestoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class PresupuestoRepuestoController
 *
 * Controlador RESTful encargado de gestionar los repuestos asociados a presupuestos.
 */
class PresupuestoRepuestoController extends Controller
{
    /**
     * Retorna todos los repuestos asociados a presupuestos.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('id_presupuesto')) {
            $repuestos = PresupuestoRepuestoService::getByPresupuesto($request->query('id_presupuesto'));
        } else {
            $repuestos = PresupuestoRepuestoService::getAll();
        }

        return $this->successResponse(
            $repuestos,
            $repuestos->isEmpty() ? 'No se encontraron repuestos de presupuestos' : 'Repuestos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo repuesto en un presupuesto.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_presupuesto' => 'required|exists:presupuestos,id_presupuesto',
            'id_repuesto' => 'required|exists:repuestos,id_repuesto',
            'costo_unitario_base' => 'nullable|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'porcentaje_ganancia' => 'nullable|numeric',
            'cantidad' => 'required|numeric|min:0',
            'monto_total_linea_base' => 'nullable|numeric|min:0',
            'monto_total_linea' => 'required|numeric|min:0',
        ]);

        $item = PresupuestoRepuestoService::create($request->all());
        if (!$item) {
            return $this->errorResponse('No se pudo registrar el repuesto en el presupuesto', 400);
        }

        return $this->successResponse($item, 'Repuesto registrado en el presupuesto correctamente', 201);
    }

    /**
     * Obtiene los datos de un detalle específico de repuesto de presupuesto.
     */
    public function show($id): JsonResponse
    {
        $item = PresupuestoRepuestoService::getOne($id);
        if (!$item) {
            return $this->errorResponse('Registro no encontrado', 404);
        }

        return $this->successResponse($item, 'Repuesto de presupuesto obtenido correctamente');
    }

    /**
     * Actualiza los datos de un repuesto en un presupuesto.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_repuesto' => 'nullable|exists:repuestos,id_repuesto',
            'costo_unitario_base' => 'nullable|numeric|min:0',
            'precio_unitario' => 'nullable|numeric|min:0',
            'porcentaje_ganancia' => 'nullable|numeric',
            'cantidad' => 'nullable|numeric|min:0',
            'monto_total_linea_base' => 'nullable|numeric|min:0',
            'monto_total_linea' => 'nullable|numeric|min:0',
        ]);

        $item = PresupuestoRepuestoService::update($id, $request->all());
        if (!$item) {
            return $this->errorResponse('Registro no encontrado', 404);
        }

        return $this->successResponse($item, 'Repuesto de presupuesto actualizado correctamente');
    }

    /**
     * Elimina un repuesto de un presupuesto.
     */
    public function destroy($id): JsonResponse
    {
        $item = PresupuestoRepuestoService::delete($id);
        if (!$item) {
            return $this->errorResponse('Registro no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($item, 'Repuesto eliminado del presupuesto correctamente');
    }
}

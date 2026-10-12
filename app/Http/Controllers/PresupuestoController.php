<?php

namespace App\Http\Controllers;

use App\Services\PresupuestoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

/**
 * Class PresupuestoController
 *
 * Controlador RESTful encargado de gestionar los presupuestos iniciales y su ciclo de vida.
 */
class PresupuestoController extends Controller
{
    /**
     * Retorna todos los presupuestos registrados en el sistema con sus detalles.
     */
    public function index(): JsonResponse
    {
        $presupuestos = PresupuestoService::getAll();
        return $this->successResponse(
            $presupuestos,
            $presupuestos->isEmpty() ? 'No se encontraron presupuestos' : 'Presupuestos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo presupuesto (inicial o extra) con repuestos y servicios.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_ingreso' => 'required_without:id_orden|exists:ingresos,id_ingreso',
            'id_orden' => 'nullable|exists:ordenes,id_orden',
            'monto_subtotal' => 'required|numeric|min:0',
            'porcentaje_iva' => 'required|numeric|min:0',
            'monto_iva' => 'required|numeric|min:0',
            'monto_total' => 'required|numeric|min:0',

            // Arreglo opcional de repuestos
            'repuestos' => 'nullable|array',
            'repuestos.*.id_repuesto' => 'required|distinct|exists:repuestos,id_repuesto',
            'repuestos.*.precio_unitario' => 'required|numeric|min:0',
            'repuestos.*.cantidad' => 'required|numeric|min:0',
            'repuestos.*.monto_total_linea' => 'required|numeric|min:0',

            // Arreglo opcional de servicios (o requerido al menos un ítem total entre repuestos y servicios)
            'servicios_taller' => 'nullable|array',
            'servicios_taller.*.id_servicio_taller' => 'required|distinct|exists:servicios_taller,id_servicio_taller',
            'servicios_taller.*.precio_unitario' => 'required|numeric|min:0',
            'servicios_taller.*.cantidad' => 'required|numeric|min:0',
            'servicios_taller.*.monto_total_linea' => 'required|numeric|min:0',
        ]);

        $data = $request->all();

        try {
            $presupuesto = PresupuestoService::create($data);
            if (!$presupuesto) {
                return $this->errorResponse('No se pudo crear el presupuesto', 400);
            }
            $mensaje = $presupuesto->tipo === 'extra'
                ? 'Presupuesto extra creado correctamente'
                : 'Presupuesto inicial creado correctamente';
            return $this->successResponse($presupuesto, $mensaje, 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Obtiene los datos de un presupuesto específico con sus relaciones.
     */
    public function show($id): JsonResponse
    {
        $presupuesto = PresupuestoService::getOne($id);
        if (!$presupuesto) {
            return $this->errorResponse('Presupuesto no encontrado', 404);
        }

        return $this->successResponse($presupuesto, 'Presupuesto obtenido correctamente');
    }

    /**
     * Acepta un presupuesto (inicial o extra).
     */
    public function aceptar(Request $request, $id): JsonResponse
    {
        $presupuesto = PresupuestoService::getOne($id);
        if (!$presupuesto) {
            return $this->errorResponse('Presupuesto no encontrado', 404);
        }

        // Si es inicial, la fecha de fin tentativa es requerida para armar la orden
        if ($presupuesto->tipo === 'inicial') {
            $request->validate([
                'fecha_fin_tentativa' => 'required|date'
            ]);
        }

        $data = $request->all();

        try {
            $presupuestoActualizado = PresupuestoService::aceptar($id, $data);
            $mensaje = $presupuesto->tipo === 'extra'
                ? 'Presupuesto extra aceptado e incorporado a la orden de trabajo correctamente'
                : 'Presupuesto inicial aceptado y orden de trabajo generada correctamente';

            return $this->successResponse($presupuestoActualizado, $mensaje);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Rechaza un presupuesto inicial (el ingreso vuelve a esperando_orden).
     */
    public function rechazar($id): JsonResponse
    {
        try {
            $presupuesto = PresupuestoService::rechazar($id);
            if (!$presupuesto) {
                return $this->errorResponse('Presupuesto no encontrado', 404);
            }

            return $this->successResponse($presupuesto, 'Presupuesto rechazado correctamente. El ingreso regresó a estado esperando_orden');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Anula un presupuesto (el ingreso vuelve a esperando_orden).
     */
    public function anular(Request $request, $id): JsonResponse
    {
        $request->validate([
            'motivo_anulacion' => 'required|string|max:250',
        ]);

        $userId = auth()->id();
        $admin = $userId ? \App\Models\Admin::where('id_user', $userId)->first() : null;
        $idAdmin = $admin ? $admin->id_admin : 1;

        try {
            $presupuesto = PresupuestoService::anular($id, $request->input('motivo_anulacion'), $idAdmin);
            if (!$presupuesto) {
                return $this->errorResponse('Presupuesto no encontrado', 404);
            }

            return $this->successResponse($presupuesto, 'Presupuesto anulado correctamente');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Elimina un presupuesto y sus detalles.
     */
    public function destroy($id): JsonResponse
    {
        $presupuesto = PresupuestoService::delete($id);
        if (!$presupuesto) {
            return $this->errorResponse('Presupuesto no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($presupuesto, 'Presupuesto eliminado correctamente');
    }
}

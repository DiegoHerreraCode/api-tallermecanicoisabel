<?php

namespace App\Http\Controllers;

use App\Services\OrdenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\OrdenEstadoOperativo;
use App\Enums\OrdenEstadoAdministrativo;
use Exception;

/**
 * Class OrdenController
 *
 * Controlador RESTful encargado de gestionar las ordenes de trabajo globales.
 * Expone las operaciones CRUD comunicándose directamente con OrdenService.
 */
class OrdenController extends Controller
{
    /**
     * Retorna todas las ordenes registradas en el sistema con sus repuestos y servicios.
     *
     * @return JsonResponse Lista de ordenes.
     */
    public function index(): JsonResponse
    {
        $ordenes = OrdenService::getAll();
        return $this->successResponse(
            $ordenes,
            $ordenes->isEmpty() ? 'No se encontraron ordenes' : 'Ordenes obtenidas correctamente'
        );
    }

    /**
     * Registra una nueva orden de trabajo con sus repuestos y servicios.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_ingreso' => 'required|exists:ingresos,id_ingreso',
            'id_tipo_orden' => 'required|exists:tipos_ordenes,id_tipo_orden',
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'fecha_fin_tentativa' => 'required|date',
            'monto_subtotal' => 'required|numeric|min:0',
            'porcentaje_iva' => 'required|numeric|min:0',
            'monto_iva' => 'required|numeric|min:0',
            'monto_total' => 'required|numeric|min:0',

            // Arreglo opcional de repuestos seleccionados
            'repuestos' => 'nullable|array',
            'repuestos.*.id_repuesto' => 'required|distinct|exists:repuestos,id_repuesto',
            'repuestos.*.precio_unitario' => 'required|numeric|min:0',
            'repuestos.*.cantidad' => 'required|numeric|min:0',
            'repuestos.*.monto_total_linea' => 'required|numeric|min:0',

            // Arreglo de servicios seleccionados (obligatorio al menos un servicio)
            'servicios_taller' => 'required|array|min:1',
            'servicios_taller.*.id_servicio_taller' => 'required|distinct|exists:servicios_taller,id_servicio_taller',
            'servicios_taller.*.id_personal' => 'nullable|exists:personal,id_personal',
            'servicios_taller.*.precio_unitario' => 'required|numeric|min:0',
            'servicios_taller.*.cantidad' => 'required|numeric|min:0',
            'servicios_taller.*.monto_total_linea' => 'required|numeric|min:0',
            'servicios_taller.*.fecha_inicio' => 'nullable|date',
            'servicios_taller.*.fecha_fin' => 'nullable|date',
        ]);

        $data = $request->all();

        try {
            $orden = OrdenService::create($data);
            if (!$orden) {
                return $this->errorResponse('Orden no creada', 400);
            }
            return $this->successResponse($orden, 'Orden creada correctamente', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Obtiene los datos de una orden específica con sus detalles.
     *
     * @param int|string $id Identificador de la orden.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $orden = OrdenService::getOne($id);
        if (!$orden) {
            return $this->errorResponse('Orden no encontrada', 404);
        }
        return $this->successResponse($orden, 'Orden obtenida correctamente');
    }

    /**
     * Actualiza una orden existente y sincroniza sus repuestos y servicios.
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_ingreso' => 'nullable|int|exists:ingresos,id_ingreso',
            'id_tipo_orden' => 'nullable|int|exists:tipos_ordenes,id_tipo_orden',
            'id_cliente' => 'nullable|int|exists:clientes,id_cliente',
            'fecha_fin' => 'nullable|date',
            'fecha_fin_tentativa' => 'nullable|date',
            'estado_operativo' => 'nullable|in:' . implode(',', OrdenEstadoOperativo::values()),
            'estado_administrativo' => 'nullable|in:' . implode(',', OrdenEstadoAdministrativo::values()),
            'monto_subtotal' => 'nullable|numeric|min:0',
            'porcentaje_iva' => 'nullable|numeric|min:0',
            'monto_iva' => 'nullable|numeric|min:0',
            'monto_total' => 'nullable|numeric|min:0',
            'monto_pendiente' => 'nullable|numeric|min:0',

            // Arreglo de repuestos para sincronizar
            'repuestos' => 'nullable|array',
            'repuestos.*.id_repuesto' => 'required|distinct|exists:repuestos,id_repuesto',
            'repuestos.*.precio_unitario' => 'required|numeric|min:0',
            'repuestos.*.cantidad' => 'required|numeric|min:0',
            'repuestos.*.monto_total_linea' => 'required|numeric|min:0',

            // Arreglo de servicios para sincronizar
            'servicios_taller' => 'nullable|array',
            'servicios_taller.*.id_servicio_taller' => 'required|distinct|exists:servicios_taller,id_servicio_taller',
            'servicios_taller.*.id_personal' => 'nullable|exists:personal,id_personal',
            'servicios_taller.*.precio_unitario' => 'required|numeric|min:0',
            'servicios_taller.*.cantidad' => 'required|numeric|min:0',
            'servicios_taller.*.monto_total_linea' => 'required|numeric|min:0',
            'servicios_taller.*.fecha_inicio' => 'nullable|date',
            'servicios_taller.*.fecha_fin' => 'nullable|date',

            'fecha_anulacion' => 'nullable|date',
            'motivo_anulacion' => 'nullable|string|max:250',
        ]);

        $data = $request->all();

        try {
            $orden = OrdenService::update($id, $data);
            if (!$orden) {
                return $this->errorResponse('Orden no encontrada', 404);
            }

            return $this->successResponse($orden, 'Orden actualizada correctamente');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Elimina una orden y sus detalles de la base de datos.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $orden = OrdenService::delete($id);
        if (!$orden) {
            return $this->errorResponse('Orden no encontrada o no se pudo eliminar', 404);
        }

        return $this->successResponse($orden, 'Orden eliminada correctamente');
    }

    /**
     * Anula una orden de trabajo y todos sus servicios asociados.
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function anular(Request $request, $id): JsonResponse
    {
        $request->validate([
            'motivo_anulacion' => 'required|string|max:250',
        ]);

        $data = $request->all();

        $userId = auth()->id();
        $admin = $userId ? \App\Models\Admin::where('id_user', $userId)->first() : null;
        $idAdmin = $admin ? $admin->id_admin : 1;

        try {
            $orden = OrdenService::anular($id, $data['motivo_anulacion'], $idAdmin);
            if (!$orden) {
                return $this->errorResponse('Orden no encontrada', 404);
            }

            return $this->successResponse($orden, 'Orden anulada correctamente');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
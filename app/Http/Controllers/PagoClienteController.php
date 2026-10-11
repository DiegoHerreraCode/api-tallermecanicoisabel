<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Services\PagoClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class PagoClienteController
 *
 * Controlador RESTful encargado de registrar y gestionar los pagos o abonos efectuados
 * por los clientes a las órdenes.
 */
class PagoClienteController extends Controller
{
    /**
     * Retorna el listado completo de pagos de clientes.
     *
     * Lógica:
     * 1. Consulta la colección de pagos mediante PagoClienteService::getAll().
     * 2. Devuelve respuesta estándar JSON con código 200.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "pagos_clientes";
     *
     * @return JsonResponse Lista de pagos.
     */
    public function index(): JsonResponse
    {
        $pagos = PagoClienteService::getAll();
        return $this->successResponse(
            $pagos,
            $pagos->isEmpty() ? 'No se encontraron pagos de clientes' : 'Pagos de clientes obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo abono o pago de cliente hacia una orden activa.
     *
     * Lógica:
     * 1. Valida cliente, orden, monto positivo, fecha, método de pago y comprobante.
     * 2. Invoca PagoClienteService::create($data), el cual verifica que la orden se encuentre en estado_operativo = 'finalizada'.
     * 3. Si la validación de negocio falla retorna 422 Unprocessable Entity.
     * 4. En caso de éxito, descuenta el monto de monto_pendiente en la orden y actualiza estado_administrativo.
     * 5. Retorna el pago creado con código HTTP 201.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "ordenes" WHERE "id_orden" = :id LIMIT 1;
     * -- INSERT INTO "pagos_clientes" ("id_pago_cliente", "id_cliente", "id_orden", "monto", ...) VALUES (...);
     * -- UPDATE "ordenes" SET "monto_pendiente" = ..., "estado_administrativo" = 'pagada' WHERE "id_orden" = :id;
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_cliente' => 'required|integer|exists:clientes,id_cliente',
            'id_orden' => 'required|integer|exists:ordenes,id_orden',
            'monto' => 'required|numeric|min:0.01',
            'fecha_pago' => 'nullable|date',
            'metodo_pago' => 'required|in:' . implode(',', MetodoPago::values()),
            'num_referencia' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        $data = $request->all();

        $pago = PagoClienteService::create($data);
        if ($pago === false) {
            return $this->errorResponse('Solo se permite registrar pagos cuando la orden está operativamente finalizada', 422);
        }
        if (!$pago) {
            return $this->errorResponse('Pago no creado', 404);
        }

        return $this->successResponse($pago, 'Pago registrado correctamente', 201);
    }

    /**
     * Consulta y entrega los datos de un pago específico por su ID.
     *
     * Lógica:
     * 1. Busca el pago vía PagoClienteService::getOne($id).
     * 2. Si no existe retorna 404, de lo contrario entrega el recurso con 200 OK.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "pagos_clientes" WHERE "id_pago_cliente" = :id LIMIT 1;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $pago = PagoClienteService::getOne($id);
        if (!$pago) {
            return $this->errorResponse('Pago no encontrado', 404);
        }

        return $this->successResponse($pago, 'Pago obtenido correctamente');
    }

    /**
     * Actualiza la información de un pago registrado.
     *
     * Lógica:
     * 1. Valida los campos proporcionados.
     * 2. Comprueba que al menos un campo haya sido provisto.
     * 3. Invoca PagoClienteService::update($id, $data) y responde con el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT * FROM "pagos_clientes" WHERE "id_pago_cliente" = :id LIMIT 1;
     * -- UPDATE "pagos_clientes" SET "monto" = ..., "updated_at" = NOW() WHERE "id_pago_cliente" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_cliente' => 'nullable|integer|exists:clientes,id_cliente',
            'id_orden' => 'nullable|integer|exists:ordenes,id_orden',
            'monto' => 'nullable|numeric|min:0.01',
            'fecha_pago' => 'nullable|date',
            'metodo_pago' => 'nullable|in:' . implode(',', MetodoPago::values()),
            'num_referencia' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        // Validar que al menos un campo sea modificado
        if (
            !$request->has('id_cliente') &&
            !$request->has('id_orden') &&
            !$request->has('monto') &&
            !$request->has('fecha_pago') &&
            !$request->has('metodo_pago') &&
            !$request->has('num_referencia') &&
            !$request->has('image')
        ) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $pago = PagoClienteService::update($id, $data);
        if (!$pago) {
            return $this->errorResponse('Pago no encontrado', 404);
        }

        return $this->successResponse($pago, 'Pago actualizado correctamente');
    }

    /**
     * Elimina un registro de pago si no está restringido.
     *
     * Lógica:
     * 1. Invoca PagoClienteService::delete($id).
     * 2. Retorna error 404 si falla o el pago con HTTP 200 si tiene éxito.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "pagos_clientes" WHERE "id_pago_cliente" = :id LIMIT 1;
     * -- DELETE FROM "pagos_clientes" WHERE "id_pago_cliente" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $pago = PagoClienteService::delete($id);
        if (!$pago) {
            return $this->errorResponse('Pago no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($pago, 'Pago eliminado correctamente');
    }
}
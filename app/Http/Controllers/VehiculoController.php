<?php

namespace App\Http\Controllers;

use App\Services\VehiculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class VehiculoController
 *
 * Controlador RESTful encargado de gestionar los vehiculos de los clientes.
 * Expone los endpoints para listar, registrar, consultar detalle, actualizar y eliminar vehiculos.
 */
class VehiculoController extends Controller
{
    /**
     * Lista todos los vehiculos registrados en la base de datos.
     *
     * Lógica:
     * 1. Consulta la colección de vehiculos mediante VehiculoService::getAll().
     * 2. Retorna respuesta JSON exitosa indicando si hay o no registros disponibles.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "vehiculos";
     *
     * @return JsonResponse Lista de vehiculos.
     */
    public function index(): JsonResponse
    {
        $vehiculos = VehiculoService::getAll();
        return $this->successResponse(
            $vehiculos,
            $vehiculos->isEmpty() ? 'No se encontraron vehiculos' : 'Vehiculos obtenidos correctamente'
        );
    }

    /**
     * Registra un nuevo cliente con sus datos de contacto y facturación.
     *
     * Lógica:
     * 1. Valida obligatoriedad y formato de cedula, correo, teléfono y dirección, exigiendo unicidad.
     * 2. Envía la data a VehiculoService::create() para asignar el ID autoincremental e insertar en la tabla.
     * 3. Retorna el vehiculo creado con código HTTP 201 (Created).
     *
     * Consultas SQL ejecutadas internamente:
     * -- Validación de unicidad:
     * -- SELECT count(*) FROM "vehiculos" WHERE "placa" = '...' LIMIT 1;
     * -- Inserción en la base de datos:
     * -- INSERT INTO "vehiculos" ("id_cliente", "id_tipo_vehiculo", "id_modelo_vehiculo", "placa", "annio", "created_at", "updated_at") VALUES (...);
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'id_tipo_vehiculo' => 'required|exists:tipos_vehiculos,id_tipo_vehiculo',
            'id_modelo_vehiculo' => 'required|exists:modelos_vehiculos,id_modelo_vehiculo',
            'placa' => 'required|string|max:50|unique:vehiculos,placa',
            'annio' => 'required|string',
        ]);

        $data = $request->all();

        $vehiculo = VehiculoService::create($data);

        if (!$vehiculo) {
            return $this->errorResponse('Vehiculo no creado', 404);
        }

        return $this->successResponse($vehiculo, 'Vehiculo creado correctamente', 201);
    }

    /**
     * Retorna la información de un vehiculo específico por su ID.
     *
     * Lógica:
     * 1. Busca el vehiculo mediante VehiculoService::getOne($id).
     * 2. Si no existe, responde con error HTTP 404; si existe, entrega el recurso con HTTP 200.
     *
     * Consulta SQL ejecutada internamente vía Eloquent:
     * -- SELECT * FROM "vehiculos" WHERE "id_vehiculo" = :id LIMIT 1;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $vehiculo = VehiculoService::getOne($id);
        if (!$vehiculo) {
            return $this->errorResponse('Vehiculo no encontrado', 404);
        }

        return $this->successResponse($vehiculo, 'Vehiculo obtenido correctamente');
    }

    /**
     * Actualiza la información de un vehiculo existente.
     *
     * Lógica:
     * 1. Valida los campos enviados ignorando el ID del vehiculo en reglas de unicidad.
     * 2. Verifica que al menos un campo haya sido provisto para actualizar.
     * 3. Ejecuta VehiculoService::update($id, $data) y retorna el registro actualizado.
     *
     * Consultas SQL ejecutadas internamente:
     * -- SELECT count(*) FROM "vehiculos" WHERE "placa" = '...' AND "id_vehiculo" != :id LIMIT 1;
     * -- SELECT * FROM "vehiculos" WHERE "id_vehiculo" = :id LIMIT 1;
     * -- UPDATE "vehiculos" SET "placa" = '...', "updated_at" = NOW() WHERE "id_vehiculo" = :id;
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'id_cliente' => 'nullable|exists:clientes,id_cliente',
            'id_tipo_vehiculo' => 'nullable|exists:tipos_vehiculos,id_tipo_vehiculo',
            'id_modelo_vehiculo' => 'nullable|exists:modelos_vehiculos,id_modelo_vehiculo',
            'placa' => "nullable|string|max:50|unique:vehiculos,placa,{$id},id_vehiculo",
            'annio' => 'nullable|string',
        ]);

        // Validar que al menos un campo sea provisto para actualizar
        if (!$request->has('id_cliente') && !$request->has('id_tipo_vehiculo') && !$request->has('id_modelo_vehiculo') && !$request->has('placa') && !$request->has('annio')) {
            return $this->errorResponse('Al menos un campo debe ser modificado', 400);
        }

        $data = $request->all();

        $vehiculo = VehiculoService::update($id, $data);
        if (!$vehiculo) {
            return $this->errorResponse('Vehiculo no encontrado', 404);
        }

        return $this->successResponse($vehiculo, 'Vehiculo actualizado correctamente');
    }

    /**
     * Elimina un vehiculo de la base de datos si no tiene restricciones de integridad referencial.
     *
     * Lógica:
     * 1. Llama a VehiculoService::delete($id).
     * 2. Si no se localiza el vehiculo o la eliminación falla, devuelve error 404.
     * 3. Si se elimina con éxito, devuelve el vehiculo eliminado.
     *
     * Consultas SQL ejecutadas internamente vía Eloquent:
     * -- SELECT * FROM "vehiculos" WHERE "id_vehiculo" = :id LIMIT 1;
     * -- DELETE FROM "vehiculos" WHERE "id_vehiculo" = :id;
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $vehiculo = VehiculoService::delete($id);
        if (!$vehiculo) {
            return $this->errorResponse('Vehiculo no encontrado o no se pudo eliminar', 404);
        }

        return $this->successResponse($vehiculo, 'Vehiculo eliminado correctamente');
    }
}
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\MarcaVehiculoController;
use App\Http\Controllers\ModeloVehiculoController;
use App\Http\Controllers\TipoVehiculoController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\TipoOrdenController;
use App\Http\Controllers\MarcaRepuestoController;
use App\Http\Controllers\RepuestoController;
use App\Http\Controllers\TipoServicioTallerController;
use App\Http\Controllers\ServicioTallerController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\VariableController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\OrdenRepuestoController;
use App\Http\Controllers\OrdenServicioTallerController;
use App\Http\Controllers\HallazgoTecnicoController;
use App\Http\Controllers\PagoClienteController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\PresupuestoRepuestoController;
use App\Http\Controllers\PresupuestoServicioTallerController;
use App\Http\Controllers\CronjobController;

// Rutas Públicas (Sin Token)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Endpoint para el Cron-Job Externo
Route::get('/cronjob/{token}', [CronjobController::class, 'run']);

// Rutas Protegidas (Requieren Header: Authorization: Bearer <token>)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);

    // Endpoints específicos para Anulación y Decisiones
    Route::post('/ingresos/{id}/anular', [IngresoController::class, 'anular']);
    Route::post('/ordenes/{id}/anular', [OrdenController::class, 'anular']);

    // Endpoints específicos para Presupuestos
    Route::post('/presupuestos/{id}/aceptar', [PresupuestoController::class, 'aceptar']);
    Route::post('/presupuestos/{id}/rechazar', [PresupuestoController::class, 'rechazar']);
    Route::post('/presupuestos/{id}/anular', [PresupuestoController::class, 'anular']);

    Route::apiResources([
        // Capa 1
        'admins' => AdminController::class,
        'clientes' => ClienteController::class,
        'marcas-vehiculos' => MarcaVehiculoController::class,
        'modelos-vehiculos' => ModeloVehiculoController::class,
        'tipos-vehiculos' => TipoVehiculoController::class,
        'vehiculos' => VehiculoController::class,
        'tipos-ordenes' => TipoOrdenController::class,
        'marcas-repuestos' => MarcaRepuestoController::class,
        'repuestos' => RepuestoController::class,
        'tipos-servicios-taller' => TipoServicioTallerController::class,
        'servicios-taller' => ServicioTallerController::class,
        'personal' => PersonalController::class,
        'variables' => VariableController::class,

        // Capa 2
        'ingresos' => IngresoController::class,
        'ordenes' => OrdenController::class,
        'ordenes-repuestos' => OrdenRepuestoController::class,
        'ordenes-servicios-taller' => OrdenServicioTallerController::class,

        // Capa 3
        'hallazgos-tecnicos' => HallazgoTecnicoController::class,
        'pagos-clientes' => PagoClienteController::class,

        // Capa 4
        'presupuestos' => PresupuestoController::class,
        'presupuestos-repuestos' => PresupuestoRepuestoController::class,
        'presupuestos-servicios-taller' => PresupuestoServicioTallerController::class,
    ]);

    // hacerlo con apiResources es equivalente a las siguientes rutas:  
    // Route::get('/admins', [AdminController::class, 'index']);
    // Route::get('/admins/{id}', [AdminController::class, 'show']);
    // Route::post('/admins', [AdminController::class, 'store']);
    // Route::put('/admins/{id}', [AdminController::class, 'update']);
    // Route::delete('/admins/{id}', [AdminController::class, 'destroy']);
});
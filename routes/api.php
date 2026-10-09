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

// Rutas Públicas (Sin Token)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Rutas Protegidas (Requieren Header: Authorization: Bearer <token>)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);

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
        'variables' => VariableController::class
    ]);

    // hacerlo con apiResources es equivalente a las siguientes rutas:  
    // Route::get('/admins', [AdminController::class, 'index']);
    // Route::get('/admins/{id}', [AdminController::class, 'show']);
    // Route::post('/admins', [AdminController::class, 'store']);
    // Route::put('/admins/{id}', [AdminController::class, 'update']);
    // Route::delete('/admins/{id}', [AdminController::class, 'destroy']);
});
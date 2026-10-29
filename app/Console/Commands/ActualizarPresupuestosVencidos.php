<?php

namespace App\Console\Commands;

use App\Services\PresupuestoService;
use Illuminate\Console\Command;

class ActualizarPresupuestosVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presupuestos:actualizar-vencidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los presupuestos pendientes cuya fecha de vencimiento ya expiró a estado rechazado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PresupuestoService::actualizarPresupuestosVencidos();
    }
}

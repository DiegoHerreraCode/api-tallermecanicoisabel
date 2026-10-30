<?php

namespace App\Services;

use App\Models\Presupuesto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PDFService
{
    /**
     * Datos maestros de la empresa emisora
     */
    public static function getDatosEmpresa(): array
    {
        return [
            'nombre' => 'Taller Mecanico Isabel',
            'rif' => 'J-30468971-3',
            'direccion' => 'Calle Falsa 1234, Barquisimeto',
            'telefono' => '+58 4145352662',
            'correo' => 'tallermecanicoisabel@gmail.com',
            'banco' => 'BANCO DE VENEZUELA',
            'tipo_cuenta' => 'CUENTA CORRIENTE',
            'numero_cuenta' => '0102-0551-22-000001113',
            'logo_public_path' => 'images/LOGO_TALLER.png',
        ];
    }

    /**
     * Genera el archivo PDF del presupuesto y lo almacena en el disco público.
     *
     * @param Presupuesto|int|string $presupuesto
     * @return string|null Ruta relativa guardada en storage/app/public o null en caso de error.
     */
    public static function generarPresupuestoPDF($presupuesto): ?string
    {
        try {
            // Asegurar que el modelo cuenta con todas sus relaciones cargadas
            if (!$presupuesto instanceof Presupuesto) {
                $presupuesto = PresupuestoService::getOne($presupuesto);
            } else {
                $presupuesto->loadMissing([
                    'repuestos.repuesto.marca',
                    'serviciosTaller.servicioTaller',
                    'ingreso.vehiculo.cliente',
                    'ingreso.vehiculo.modelo.marca',
                    'ingreso.vehiculo.tipoVehiculo',
                    'ingreso.tipoOrden',
                    'orden.cliente',
                    'orden.tipoOrden',
                    'admin',
                ]);
            }

            if (!$presupuesto) {
                Log::error('PDFService::generarPresupuestoPDF - Presupuesto no encontrado');
                return null;
            }

            // Determinar cliente y tipo de orden a partir del presupuesto
            $cliente = $presupuesto->orden?->cliente
                ?? $presupuesto->ingreso?->vehiculo?->cliente;

            $tipoOrden = $presupuesto->orden?->tipoOrden
                ?? $presupuesto->ingreso?->tipoOrden;

            $vehiculo = $presupuesto->ingreso?->vehiculo;

            $empresa = self::getDatosEmpresa();

            // Preparar logo en Base64 para máxima compatibilidad con DomPDF
            $logoBase64 = null;
            $posiblesRutasLogo = [
                public_path($empresa['logo_public_path']),
                public_path('images/LOGO_TALLER.png'),
                public_path('storage/' . $empresa['logo_public_path']),
                public_path('storage/images/LOGO_TALLER.png'),
                storage_path('app/public/' . $empresa['logo_public_path']),
                storage_path('app/public/images/LOGO_TALLER.png'),
            ];

            foreach ($posiblesRutasLogo as $ruta) {
                if (file_exists($ruta)) {
                    $imageData = file_get_contents($ruta);
                    $mimeType = mime_content_type($ruta) ?: 'image/png';
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    break;
                }
            }

            $data = [
                'presupuesto' => $presupuesto,
                'cliente' => $cliente,
                'tipoOrden' => $tipoOrden,
                'vehiculo' => $vehiculo,
                'empresa' => $empresa,
                'logoBase64' => $logoBase64,
            ];

            $pdf = Pdf::loadView('pdf.Presupuesto', $data);
            $pdf->setPaper('letter', 'portrait');

            $filename = 'presupuesto_' . ($presupuesto->num_presupuesto ?: $presupuesto->id_presupuesto) . '_' . time() . '.pdf';
            $path = 'presupuestos/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());

            return $path;
        } catch (\Throwable $e) {
            Log::error('Error generating Presupuesto PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
<?php

namespace App\Models;

use App\Models\ApiModel;
use App\Enums\MetodoPago;

/**
 * Modelo PagoCliente
 * 
 * Gestiona los pagos / abonos efectuados por los clientes a sus �rdenes de trabajo.
 * Permite control de amortizaciones, trazabilidad de comprobantes y flujo de anulaci�n con auditor�a.
 */
class PagoCliente extends ApiModel
{
    const IMAGE_PATH = 'pagos_clientes';
    const IMAGE_FIELD = 'image'; // campo que guarda el nombre original de la imagen
    const IMAGE_PATH_FIELD = 'imagePath'; // campo que guarda la ruta relativa de la imagen
    protected $table = 'pagos_clientes';
    protected $primaryKey = 'id_pago_cliente';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_orden',
        'monto',
        'fecha_pago',
        'metodo_pago',
        'num_referencia',
        'image',
        'imagePath'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'id_orden', 'id_orden');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KardexMovement extends Model
{
    /**
     * Registro INMUTABLE: solo se insertan filas, nunca se editan ni eliminan.
     * No exponer rutas PUT/PATCH/DELETE sobre este modelo.
     */
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'balance_quantity',
        'balance_value',
        'movement_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity'         => 'decimal:4',
        'unit_cost'        => 'decimal:4',
        'balance_quantity'  => 'decimal:4',
        'balance_value'    => 'decimal:4',
        'movement_date'    => 'datetime',
        'created_at'       => 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Modelo origen del movimiento:
     * WarehouseReception | TransferOrder | DeliveryNote | etc.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }
}

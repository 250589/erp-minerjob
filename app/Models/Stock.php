<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    /**
     * Esta tabla se actualiza EXCLUSIVAMENTE desde KardexService.
     * No exponer en rutas de escritura directa desde el frontend.
     */
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'average_cost',
        'updated_at',
    ];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'average_cost' => 'decimal:4',
        'updated_at'   => 'datetime',
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

    // ─── Accessor ─────────────────────────────────────────────

    /** Valor total del stock: cantidad × costo promedio */
    public function getTotalValueAttribute(): float
    {
        return round($this->quantity * $this->average_cost, 4);
    }
}

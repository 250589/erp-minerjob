<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'requirement_id',
        'product_id',
        'unit_id',
        'description',
        'quantity',
        'estimated_unit_price',
    ];

    protected $casts = [
        'quantity'              => 'decimal:4',
        'estimated_unit_price'  => 'decimal:4',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    // ─── Accessor ─────────────────────────────────────────────

    public function getSubtotalAttribute(): float
    {
        if (is_null($this->estimated_unit_price)) {
            return 0.0;
        }

        return round((float) $this->quantity * (float) $this->estimated_unit_price, 2);
    }
}

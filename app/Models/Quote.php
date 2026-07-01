<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'quote_request_id', 'supplier_id', 'code',
        'currency', 'exchange_rate', 'payment_term_days',
        'delivery_term_days', 'validity_date',
        'subtotal', 'tax', 'total', 'notes', 'file_path',
        'status', 'received_at',
    ];

    protected $casts = [
        'validity_date' => 'date:Y-m-d',
        'received_at'   => 'datetime:d/m/Y',
        'subtotal'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'total'         => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'recibida'  => 'info',
            'comparada' => 'warning',
            'aprobada'  => 'success',
            'rechazada' => 'error',
            default     => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'recibida'  => 'Recibida',
            'comparada' => 'Comparada',
            'aprobada'  => 'Aprobada',
            'rechazada' => 'Rechazada',
            default     => $this->status,
        };
    }

    // ─── Total en moneda base (PEN) ───────────────────────────

    public function getTotalPenAttribute(): float
    {
        return round($this->total * $this->exchange_rate, 2);
    }
}

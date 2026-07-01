<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'quote_id', 'requirement_id', 'supplier_id', 'code',
        'currency', 'exchange_rate',
        'subtotal', 'tax', 'total',
        'payment_term_days', 'delivery_term_days',
        'delivery_address', 'notes', 'status',
        'created_by', 'approved_by',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'total'         => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'created_at'    => 'datetime:d/m/Y H:i',
        'updated_at'    => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Generación de código ─────────────────────────────────

    public static function generateCode(): string
    {
        $year  = now()->year;
        $count = static::where('code', 'like', "OC-{$year}-%")
            ->lockForUpdate()->count();
        return sprintf('OC-%d-%04d', $year, $count + 1);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'generada'  => 'warning',
            'enviada'   => 'info',
            'facturada' => 'primary',
            'pagada'    => 'success',
            'anulada'   => 'error',
            default     => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'generada'  => 'Generada',
            'enviada'   => 'Enviada',
            'facturada' => 'Facturada',
            'pagada'    => 'Pagada',
            'anulada'   => 'Anulada',
            default     => $this->status,
        };
    }
}

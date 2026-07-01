<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseReception extends Model
{
    protected $fillable = [
        'purchase_order_id', 'warehouse_id', 'invoice_id', 'code',
        'received_by', 'received_at', 'status', 'observations',
    ];

    protected $casts = [
        'received_at' => 'datetime:d/m/Y H:i',
        'created_at'  => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseReceptionItem::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ─── Código correlativo ───────────────────────────────────

    public static function generateCode(): string
    {
        $year  = now()->year;
        $count = static::where('code', 'like', "REC-{$year}-%")
            ->lockForUpdate()->count();
        return sprintf('REC-%d-%04d', $year, $count + 1);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completa'  => 'success',
            'parcial'   => 'warning',
            'observada' => 'error',
            default     => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completa'  => 'Completa',
            'parcial'   => 'Parcial',
            'observada' => 'Observada',
            default     => $this->status,
        };
    }

    // Para KardexService (referencia polimórfica)
    public function getMorphClass(): string
    {
        return 'WarehouseReception';
    }
}

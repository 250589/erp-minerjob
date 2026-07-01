<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransferOrder extends Model
{
    protected $fillable = [
        'origin_warehouse_id', 'destination_warehouse_id',
        'requested_by', 'code', 'status',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferOrderItem::class);
    }

    public function guide(): HasOne
    {
        return $this->hasOne(TransferGuide::class);
    }

    public function reception(): HasOne
    {
        return $this->hasOne(TransferReception::class);
    }

    // ─── Código correlativo ───────────────────────────────────

    public static function generateCode(): string
    {
        $year  = now()->year;
        $count = static::where('code', 'like', "TR-{$year}-%")
            ->lockForUpdate()->count();
        return sprintf('TR-%d-%04d', $year, $count + 1);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'creada'      => 'warning',
            'en_transito' => 'info',
            'recibida'    => 'success',
            'rechazada'   => 'error',
            default       => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'creada'      => 'Creada',
            'en_transito' => 'En Tránsito',
            'recibida'    => 'Recibida',
            'rechazada'   => 'Rechazada',
            default       => $this->status,
        };
    }

    // Para KardexService (referencia polimórfica)
    public function getMorphClass(): string
    {
        return 'TransferOrder';
    }
}

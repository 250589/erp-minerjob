<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $fillable = [
        'warehouse_id', 'area_id', 'requirement_id', 'code',
        'requested_by', 'delivered_by', 'delivered_at', 'notes', 'status',
    ];

    protected $casts = [
        'delivered_at' => 'datetime:d/m/Y H:i',
        'created_at'   => 'datetime:d/m/Y H:i',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public static function generateCode(): string
    {
        $year  = now()->year;
        $count = static::where('code', 'like', "NE-{$year}-%")
            ->lockForUpdate()->count();
        return sprintf('NE-%d-%04d', $year, $count + 1);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'borrador'  => 'warning',
            'entregada' => 'success',
            default     => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador'  => 'Borrador',
            'entregada' => 'Entregada',
            default     => $this->status,
        };
    }

    // Para KardexService (referencia polimórfica)
    public function getMorphClass(): string
    {
        return 'DeliveryNote';
    }
}
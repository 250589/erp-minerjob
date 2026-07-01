<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requirement extends Model
{
    protected $fillable = [
        'requester_id',
        'area_id',
        'code',
        'justification',
        'required_date',
        'status',
    ];

    protected $casts = [
        'required_date' => 'date:Y-m-d',
        'created_at'    => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequirementItem::class);
    }

    // ─── Generación de código correlativo ─────────────────────

    /**
     * Genera el siguiente código REQ-YYYY-NNNN.
     * Usa lockForUpdate para evitar duplicados bajo concurrencia.
     */
    public static function generateCode(): string
    {
        $year  = now()->year;
        $count = static::where('code', 'like', "REQ-{$year}-%")
            ->lockForUpdate()
            ->count();

        return sprintf('REQ-%d-%04d', $year, $count + 1);
    }

    // ─── Accessors de estado ──────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pendiente'     => 'warning',
            'en_cotizacion' => 'info',
            'aprobado'      => 'success',
            'rechazado'     => 'error',
            'convertido_oc' => 'primary',
            'completado'    => 'secondary',
            default         => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente'     => 'Pendiente',
            'en_cotizacion' => 'En Cotización',
            'aprobado'      => 'Aprobado',
            'rechazado'     => 'Rechazado',
            'convertido_oc' => 'Convertido a OC',
            'completado'    => 'Completado',
            default         => $this->status,
        };
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}

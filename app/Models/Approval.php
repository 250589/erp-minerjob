<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'approvable_type', 'approvable_id',
        'requested_by', 'approver_id',
        'status', 'comments', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime:d/m/Y H:i',
        'created_at' => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    /** Modelo que se está aprobando (Quote, etc.) */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // ─── Factory method ───────────────────────────────────────

    /**
     * Crea una aprobación pendiente para una cotización seleccionada.
     * Se llama desde QuoteController::selectWinner()
     */
    public static function createForQuote(Quote $quote, int $requestedBy): self
    {
        return static::create([
            'approvable_type' => Quote::class,
            'approvable_id'   => $quote->id,
            'requested_by'    => $requestedBy,
            'status'          => 'pendiente',
        ]);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'warning',
            'aprobado'  => 'success',
            'rechazado' => 'error',
            default     => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'aprobado'  => 'Aprobado',
            'rechazado' => 'Rechazado',
            default     => $this->status,
        };
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }
}

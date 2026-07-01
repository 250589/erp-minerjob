<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuoteRequest extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'requirement_id', 'created_by', 'code',
        'sent_date', 'deadline_date', 'status',
    ];

    protected $casts = [
        'sent_date'    => 'date:Y-m-d',
        'deadline_date'=> 'date:Y-m-d',
        'created_at'   => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(QuoteRequestSupplier::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function comparison(): HasOne
    {
        return $this->hasOne(QuoteComparison::class);
    }

    // ─── Generación de código ─────────────────────────────────

    public static function generateCode(): string
    {
        $year  = now()->year;
        $count = static::where('code', 'like', "SC-{$year}-%")
            ->lockForUpdate()->count();
        return sprintf('SC-%d-%04d', $year, $count + 1);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'abierta'   => 'success',
            'cerrada'   => 'primary',
            'cancelada' => 'error',
            default     => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'abierta'   => 'Abierta',
            'cerrada'   => 'Cerrada',
            'cancelada' => 'Cancelada',
            default     => $this->status,
        };
    }
}

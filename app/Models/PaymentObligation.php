<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentObligation extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'invoice_id', 'accounting_entry_id',
        'amount', 'currency', 'due_date', 'status',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'due_date'   => 'date:Y-m-d',
        'created_at' => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->status === 'pendiente'
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function latestPayment(): ?Payment
    {
        return $this->payments()->latest('created_at')->first();
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        if ($this->isOverdue()) return 'error';

        return match ($this->status) {
            'pendiente' => 'warning',
            'pagado'    => 'success',
            'vencido'   => 'error',
            default     => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isOverdue() && $this->status === 'pendiente') return 'Vencida';

        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'pagado'    => 'Pagado',
            'vencido'   => 'Vencido',
            default     => $this->status,
        };
    }

    // ─── Scope ────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }
}

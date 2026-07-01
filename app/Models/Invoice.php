<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $fillable = [
        'purchase_order_id', 'supplier_id', 'series', 'number',
        'issue_date', 'currency', 'exchange_rate',
        'subtotal', 'tax', 'total',
        'status', 'file_path', 'received_at',
        'validated_by', 'validated_at',
    ];

    protected $casts = [
        'issue_date'   => 'date:Y-m-d',
        'received_at'  => 'datetime:d/m/Y H:i',
        'validated_at' => 'datetime:d/m/Y H:i',
        'created_at'   => 'datetime:d/m/Y H:i',
        'subtotal'     => 'decimal:2',
        'tax'          => 'decimal:2',
        'total'        => 'decimal:2',
        'exchange_rate'=> 'decimal:4',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(InvoiceObservation::class);
    }

    public function accountingEntry(): HasOne
    {
        return $this->hasOne(AccountingEntry::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function getFullNumberAttribute(): string
    {
        return $this->series
            ? "{$this->series}-{$this->number}"
            : $this->number;
    }

    public function hasPendingObservations(): bool
    {
        return $this->observations()->whereNull('resolved_at')->exists();
    }

    // ─── Accessors de estado ──────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'recibida'    => 'default',
            'en_revision' => 'info',
            'observada'   => 'warning',
            'validada'    => 'primary',
            'registrada'  => 'success',
            'pagada'      => 'secondary',
            default       => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'recibida'    => 'Recibida',
            'en_revision' => 'En Revisión',
            'observada'   => 'Observada',
            'validada'    => 'Validada',
            'registrada'  => 'Registrada',
            'pagada'      => 'Pagada',
            default       => $this->status,
        };
    }

    // ─── Steps del flujo disponibles por estado ───────────────

    public function getAvailableActionsAttribute(): array
    {
        return match ($this->status) {
            'recibida'    => ['start_review'],
            'en_revision' => ['observe', 'validate'],
            'observada'   => ['resolve_observation'],
            'validada'    => ['create_accounting_entry'],
            default       => [],
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'payment_obligation_id',
        'payment_date', 'method', 'amount', 'currency', 'exchange_rate',
        'origin_account', 'destination_account', 'reference_number',
        'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'payment_date'  => 'date:Y-m-d',
        'created_at'    => 'datetime:d/m/Y H:i',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function paymentObligation(): BelongsTo
    {
        return $this->belongsTo(PaymentObligation::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(PaymentVoucher::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'transferencia' => 'Transferencia Bancaria',
            'deposito'      => 'Depósito',
            'cheque'        => 'Cheque',
            default         => $this->method,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'registrado' => 'warning',
            'confirmado' => 'success',
            default      => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'registrado' => 'Registrado',
            'confirmado' => 'Confirmado',
            default      => $this->status,
        };
    }
}

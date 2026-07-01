<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_name',
        'trade_name',
        'tax_id',
        'address',
        'phone',
        'email',
        'currency_default',
        'payment_term_days',
        'bank_name',
        'bank_account',
        'status',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function supplierUsers(): HasMany
    {
        return $this->hasMany(SupplierUser::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'activo');
    }

    // ─── Accessor: nombre para mostrar en selects ─────────────

    public function getDisplayNameAttribute(): string
    {
        return $this->trade_name
            ? "{$this->trade_name} ({$this->tax_id})"
            : "{$this->business_name} ({$this->tax_id})";
    }
}

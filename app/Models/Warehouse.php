<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'parent_warehouse_id',
        'manager_user_id',
        'code',
        'name',
        'type',
        'address',
        'status',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    /** Almacén padre (si es subalmacén) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'parent_warehouse_id');
    }

    /** Subalmacenes que dependen de este almacén principal */
    public function children(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'parent_warehouse_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function kardexMovements(): HasMany
    {
        return $this->hasMany(KardexMovement::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'activo');
    }

    public function scopePrincipal($query)
    {
        return $query->where('type', 'principal');
    }

    public function scopeSubalmacen($query)
    {
        return $query->where('type', 'subalmacen');
    }
}

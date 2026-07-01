<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'parent_warehouse_id', 'manager_user_id', 'code', 'name',
        'type', 'address', 'status',
    ];

    // ─── Relaciones ───────────────────────────────────────────
    public function parent(): BelongsTo    { return $this->belongsTo(Warehouse::class, 'parent_warehouse_id'); }
    public function children(): HasMany    { return $this->hasMany(Warehouse::class, 'parent_warehouse_id'); }
    public function manager(): BelongsTo   { return $this->belongsTo(User::class, 'manager_user_id'); }
    public function stocks(): HasMany      { return $this->hasMany(Stock::class); }

    // ─── Scopes ───────────────────────────────────────────────
    public function scopeActive($query)    { return $query->where('status', 'activo'); }
    public function scopePrincipal($query) { return $query->where('type', 'principal'); }
    public function scopeSubalmacen($query){ return $query->where('type', 'subalmacen'); }

    // ─── Accessors ────────────────────────────────────────────
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'principal'  => 'primary',
            'subalmacen' => 'secondary',
            'transito'   => 'warning',
            default      => 'default',
        };
    }
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'principal'  => 'Almacén Principal',
            'subalmacen' => 'Subalmacén',
            'transito'   => 'Tránsito',
            default      => $this->type,
        };
    }
    public function getStatusColorAttribute(): string
    {
        return $this->status === 'activo' ? 'success' : 'error';
    }
}
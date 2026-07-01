<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'unit_id', 'sku', 'name', 'description',
        'min_stock', 'max_stock', 'markup_percentage',
        'current_purchase_price', 'current_sale_price', 'status',
    ];

    protected $casts = [
        'min_stock'              => 'decimal:4',
        'max_stock'              => 'decimal:4',
        'markup_percentage'      => 'decimal:2',
        'current_purchase_price' => 'decimal:4',
        'current_sale_price'     => 'decimal:4',
    ];

    // ─── Relaciones ───────────────────────────────────────────
    public function category(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'category_id'); }
    public function unit(): BelongsTo     { return $this->belongsTo(UnitOfMeasure::class, 'unit_id'); }
    public function stocks(): HasMany     { return $this->hasMany(Stock::class); }

    // ─── Scopes ───────────────────────────────────────────────
    public function scopeActive($query)   { return $query->where('status', 'activo'); }
    public function scopeInactive($query) { return $query->where('status', 'inactivo'); }

    // ─── Accessors ────────────────────────────────────────────
    public function getStatusColorAttribute(): string
    {
        return $this->status === 'activo' ? 'success' : 'error';
    }
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'activo' ? 'Activo' : 'Inactivo';
    }
}
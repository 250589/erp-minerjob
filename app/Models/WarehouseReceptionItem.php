<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseReceptionItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'warehouse_reception_id', 'purchase_order_item_id', 'product_id',
        'quantity_ordered', 'quantity_received',
        'unit_purchase_price', 'markup_percentage_applied',
        'unit_sale_price', 'condition_status', 'notes',
    ];

    protected $casts = [
        'quantity_ordered'          => 'decimal:4',
        'quantity_received'         => 'decimal:4',
        'unit_purchase_price'       => 'decimal:4',
        'markup_percentage_applied' => 'decimal:2',
        'unit_sale_price'           => 'decimal:4',
    ];

    public function warehouseReception(): BelongsTo
    {
        return $this->belongsTo(WarehouseReception::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Diferencia cantidad: recibida vs ordenada
    public function getQuantityDiffAttribute(): float
    {
        return round((float)$this->quantity_received - (float)$this->quantity_ordered, 4);
    }
}

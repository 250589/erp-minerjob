<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transfer_order_id', 'product_id',
        'quantity_requested', 'quantity_sent',
        'quantity_received', 'unit_cost',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_sent'      => 'decimal:4',
        'quantity_received'  => 'decimal:4',
        'unit_cost'          => 'decimal:4',
    ];

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

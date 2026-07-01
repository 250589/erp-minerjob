<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferReceptionItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transfer_reception_id', 'transfer_order_item_id',
        'quantity_received', 'condition_status', 'notes',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
    ];

    public function transferReception(): BelongsTo
    {
        return $this->belongsTo(TransferReception::class);
    }

    public function transferOrderItem(): BelongsTo
    {
        return $this->belongsTo(TransferOrderItem::class);
    }
}

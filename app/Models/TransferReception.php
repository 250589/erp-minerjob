<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferReception extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'received_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'transfer_order_id', 'received_by',
        'received_at', 'status', 'observations',
    ];

    protected $casts = [
        'received_at' => 'datetime:d/m/Y H:i',
    ];

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrder::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferReceptionItem::class);
    }
}

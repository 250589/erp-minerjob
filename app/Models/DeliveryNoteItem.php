<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'delivery_note_id', 'product_id',
        'quantity_requested', 'quantity_delivered',
        'unit_cost', 'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_delivered' => 'decimal:4',
        'unit_cost'          => 'decimal:4',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceObservation extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'invoice_id', 'comment', 'created_by', 'resolved_at',
    ];

    protected $casts = [
        'created_at'  => 'datetime:d/m/Y H:i',
        'resolved_at' => 'datetime:d/m/Y H:i',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return is_null($this->resolved_at);
    }
}

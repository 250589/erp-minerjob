<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteComparison extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'generated_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'quote_request_id', 'selected_quote_id',
        'generated_by', 'comparison_data', 'generated_at',
    ];

    protected $casts = [
        'comparison_data' => 'array',
        'generated_at'    => 'datetime',
    ];

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function selectedQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'selected_quote_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

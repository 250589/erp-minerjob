<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingEntry extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'invoice_id', 'entry_number', 'entry_date',
        'description', 'total_debit', 'total_credit',
        'status', 'created_by',
    ];

    protected $casts = [
        'entry_date'   => 'date:Y-m-d',
        'created_at'   => 'datetime:d/m/Y H:i',
        'total_debit'  => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AccountingEntryDetail::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public static function generateEntryNumber(): string
    {
        $year  = now()->year;
        $month = now()->format('m');
        $count = static::where('entry_number', 'like', "AS-{$year}{$month}-%")
            ->lockForUpdate()->count();
        return sprintf('AS-%s%s-%04d', $year, $month, $count + 1);
    }
}

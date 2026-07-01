<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferGuide extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'issued_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'transfer_order_id', 'guide_number',
        'issued_by', 'issued_at', 'file_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime:d/m/Y H:i',
    ];

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrder::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public static function generateNumber(): string
    {
        $year  = now()->year;
        $count = static::where('guide_number', 'like', "GT-{$year}-%")
            ->lockForUpdate()->count();
        return sprintf('GT-%d-%04d', $year, $count + 1);
    }
}

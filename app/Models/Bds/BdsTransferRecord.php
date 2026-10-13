<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BdsTransferRecord extends Model
{
    protected $table = 'bds_transfer_records';

    protected $fillable = [
        'batch_id',
        'pallet_id',
        'transport_id',
        'item_code',
        'transport_mode',
        'quantity',
        'start_time',
        'end_time',
        'duration_seconds',
        'throughput',
        'weight_kg',
        'source_client',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_seconds' => 'integer',
            'throughput' => 'decimal:2',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function getTotalWeightKgAttribute(): float
    {
        $trayWeight = BdsTrayRecord::where('pallet_id', $this->pallet_id)->sum('weight_kg');
        if ($trayWeight > 0) {
            return round((float) $trayWeight, 2);
        }

        return (float) ($this->weight_kg ?? ($this->quantity * 1.6));
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BdsBatch::class, 'batch_id', 'batch_id');
    }
}

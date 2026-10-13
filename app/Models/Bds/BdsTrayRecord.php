<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BdsTrayRecord extends Model
{
    protected $table = 'bds_tray_records';

    protected $fillable = [
        'batch_id',
        'pallet_id',
        'transport_id',
        'tray_id',
        'item_code',
        'transport_mode',
        'depalletize_time',
        'feeder_start_time',
        'feeder_end_time',
        'duration_seconds',
        'throughput',
        'net_weight_gm',
        'weight_kg',
    ];

    protected function casts(): array
    {
        return [
            'depalletize_time' => 'datetime',
            'feeder_start_time' => 'datetime',
            'feeder_end_time' => 'datetime',
            'duration_seconds' => 'integer',
            'throughput' => 'decimal:2',
            'net_weight_gm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function getTransportIdAttribute(): ?string
    {
        return $this->attributes['transport_id']
            ?? BdsTransferRecord::where('pallet_id', $this->pallet_id)->value('transport_id')
            ?? '-';
    }

    public function getWeightKgAttribute($value): float
    {
        if ($value !== null && (float) $value > 0) {
            return (float) $value;
        }

        $gm = (float) ($this->attributes['net_weight_gm'] ?? 0);

        return $gm > 0 ? round($gm / 1000, 2) : 0.00;
    }

    public function getFeederDurationSecondsAttribute(): int
    {
        return (int) ($this->attributes['duration_seconds'] ?? 0);
    }

    public function getFeederThroughputAttribute(): float
    {
        return (float) ($this->attributes['throughput'] ?? 0);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BdsBatch::class, 'batch_id', 'batch_id');
    }
}

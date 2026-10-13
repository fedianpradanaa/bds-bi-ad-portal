<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdsBatch extends Model
{
    protected $table = 'bds_batches';

    protected $primaryKey = 'batch_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'batch_id',
        'operational_date',
        'transport_mode',
        'operator_name',
        'supervisor_name',
        'supervisor_nip',
        'team_leader_name',
        'team_leader_nip',
        'start_time',
        'end_time',
        'duration_seconds',
        'total_bags',
        'total_weight_kg',
        'throughput_ktg_j',
        'idle_duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'operational_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_seconds' => 'integer',
            'total_bags' => 'integer',
            'total_weight_kg' => 'decimal:2',
            'throughput_ktg_j' => 'decimal:2',
            'idle_duration_seconds' => 'integer',
        ];
    }

    public function transferRecords(): HasMany
    {
        return $this->hasMany(BdsTransferRecord::class, 'batch_id', 'batch_id');
    }

    public function trayRecords(): HasMany
    {
        return $this->hasMany(BdsTrayRecord::class, 'batch_id', 'batch_id');
    }
}

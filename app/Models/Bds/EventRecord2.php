<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class EventRecord2 extends Model
{
    protected $table = 'event_record_2';

    protected $fillable = [
        'timestamp',
        'position',
        'transport_id',
        'source_location',
        'target_location',
        'transport_mode',
        'category',
        'denomination',
        'emission',
        'item_number',
        'quantity',
        'batch_number',
        'tray_id',
        'pallet_id',
        'weight',
        'source_client',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }
}

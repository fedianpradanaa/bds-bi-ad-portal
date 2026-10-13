<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class EventRecord3 extends Model
{
    protected $table = 'event_record_3';

    protected $fillable = [
        'timestamp',
        'sender',
        'receiver',
        'batch_number',
        'master_hu_id',
        'master_hu_type',
        'hu_id',
        'hu_type',
        'transport_mode',
        'weight',
        'item_number',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }
}

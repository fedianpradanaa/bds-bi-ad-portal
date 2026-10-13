<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class BdsShredderEvent extends Model
{
    protected $table = 'bds_shredder_events';

    protected $fillable = [
        'event_time',
        'shredder_id',
        'idle_state',
        'shredder_event',
    ];

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
        ];
    }
}

<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class EventRecord4 extends Model
{
    protected $table = 'event_record_4';

    protected $fillable = [
        'timestamp',
        'shredder',
        'idle_state',
        'time',
        'shredder_event',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }
}

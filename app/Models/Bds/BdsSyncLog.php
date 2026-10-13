<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class BdsSyncLog extends Model
{
    protected $table = 'bds_sync_logs';

    protected $fillable = [
        'source_type',
        'files_processed',
        'rows_inserted',
        'status',
        'message',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'files_processed' => 'integer',
            'rows_inserted' => 'integer',
        ];
    }
}

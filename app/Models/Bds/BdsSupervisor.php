<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class BdsSupervisor extends Model
{
    protected $table = 'bds_supervisors';

    protected $fillable = [
        'name',
        'nip',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

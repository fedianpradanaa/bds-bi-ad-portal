<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class BdsEmission extends Model
{
    protected $table = 'bds_emissions';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }
}

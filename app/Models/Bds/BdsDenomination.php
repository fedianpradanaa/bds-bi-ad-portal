<?php

namespace App\Models\Bds;

use Illuminate\Database\Eloquent\Model;

class BdsDenomination extends Model
{
    protected $table = 'bds_denominations';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'value',
        'currency_type',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }
}

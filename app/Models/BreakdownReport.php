<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakdownReport extends Model
{
    use HasFactory;

    protected $table = 'breakdown_reports';

    protected $fillable = [
        'date',
        'unique_id',
        'event_start',
        'event_unit',
        'event_number',
        'event_stop',
        'duration',
        'eng_description',
        'ind_description',
        'source_file',
    ];
}
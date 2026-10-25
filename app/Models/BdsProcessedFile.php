<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BdsProcessedFile extends Model
{
    protected $table = 'bds_processed_files';
    
    protected $fillable = [
        'report_type',
        'file_path',
        'file_hash',
        'rows_count',
        'file_mtime',
    ];
}

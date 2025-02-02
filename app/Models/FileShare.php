<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileShare extends Model
{
    protected $fillable = [
        'file_id',
        'link',
        'link_type',
        'access_type',
        'is_active',
    ];

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }
}

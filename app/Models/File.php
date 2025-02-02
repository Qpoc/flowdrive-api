<?php

namespace App\Models;

use App\Models\User;
use App\Models\Folder;
use App\Models\FileShare;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'path',
        'size',
        'folder_id',
        'created_by',
        'deleted_by',
    ];

    public function sharedLink(){
        return $this->hasOne(FileShare::class, 'file_id', 'id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function folderWithTrashed()
    {
        return $this->belongsTo(Folder::class, 'folder_id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = [];
        $breadcrumbs[] = $this; // push the file
        $current = $this->folder;

        while ($current) {
            $breadcrumbs[] = $current;
            $current = $current->parent;
        }


        return array_reverse($breadcrumbs); // Reverse to have root first
    }
}

<?php

namespace App\Models;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'size',
        'parent_id',
        'created_by',
        'deleted_by',
    ];

    protected $appends = ['is_leaf'];

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function parentWithTrashed()
    {
        return $this->belongsTo(Folder::class, 'parent_id')->withTrashed();
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function getIsLeafAttribute()
    {
        return $this->children->count() === 0;
    }

    public function isParentFolderDeleted()
    {
        if ($this->parentWithTrashed) {
            if ($this->parentWithTrashed->deleted_at !== null) {
                return $this->parentWithTrashed->deleted_at;
            }

            return $this->parentWithTrashed->isParentFolderDeleted();
        }
    }

    public function forceDeleteFiles()
    {
        $files = $this->files()->get();

        foreach ($files as $file) {
            // Delete the physical file from storage
            Storage::disk('local')->delete("$file->path");

            // Perform the force delete (permanently delete from database)
            $file->forceDelete();
        }

        if ($this->children) {
            foreach ($this->children as $child) {
                $child->forceDeleteFiles();
            }
        }
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            $breadcrumbs[] = $current;
            $current = $current->parent;
        }

        return array_reverse($breadcrumbs); // Reverse to have root first
    }
}

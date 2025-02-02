<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FilePolicy
{

    public function show(User $user, File $file): Response
    {
        if ($file?->folderWithTrashed?->isParentFolderDeleted() || $file?->folderWithTrashed?->deleted_at !== null) {
            return Response::denyAsNotFound(__('file.not_found'));
        }
        return $user->id === $file->created_by ? Response::allow() : Response::deny('You are not authorized to view this file.');
    }

    public function download(User $user, File $file): Response
    {
        return $user->id === $file->created_by ? Response::allow() : Response::deny('You are not authorized to download this file.');
    }

    public function rename(User $user, File $file): Response
    {
        return $user->id === $file->created_by ? Response::allow() : Response::deny('You are not authorized to rename this file.');
    }

    // public function destroy(User $user, File $file): Response
    // {
    //     return $user->id === $file->created_by ? Response::allow() : Response::deny('You are not authorized to delete this file.');
    // }

    public function forceDelete(User $user, File $file): Response
    {
        return $user->id === $file->created_by ? Response::allow() : Response::deny('You are not authorized to delete this file.');
    }

    public function restore(User $user, File $file): Response
    {
        return $user->id === $file->created_by ? Response::allow() : Response::deny('You are not authorized to restore this file.');
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Folder;
use F9Web\ApiResponseHelpers;
use Illuminate\Auth\Access\Response;


class FolderPolicy
{
    use ApiResponseHelpers;

    public function show(User $user, Folder $folder)
    {
        if ($folder->isParentFolderDeleted()) {
            return Response::denyAsNotFound(__('folder.not_found'));
        }
        return $user->id === $folder->created_by ? Response::allow() : Response::deny('You are not authorized to view this file.');
    }

    public function rename(User $user, Folder $folder): Response
    {
        return $user->id === $folder->created_by ? Response::allow() : Response::deny('You are not authorized to rename this file.');
    }
}

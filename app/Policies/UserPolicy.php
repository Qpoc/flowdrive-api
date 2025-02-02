<?php

namespace App\Policies;


use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function getFiles(User $auth, User $user)
    {
        return $auth->id === $user->id ? Response::allow() : Response::deny('You are not authorized to view this file.');
    }

    public function getFolders(User $auth, User $user)
    {
        return $auth->id === $user->id ? Response::allow() : Response::deny('You are not authorized to view this file.');
    }

    public function getTrash(User $auth, User $user)
    {
        return $auth->id === $user->id ? Response::allow() : Response::deny('You are not authorized to view this file.');
    }
}

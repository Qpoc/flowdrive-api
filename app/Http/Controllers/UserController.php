<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use App\Models\Folder;
use Illuminate\Http\Request;
use F9Web\ApiResponseHelpers;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    use ApiResponseHelpers;

    public function index(Request $request)
    {
        return $this->respondWithSuccess([
            'message' => 'success',
            'data' => User::where('email', 'like', '%' . $request->search . '%')->take($request->limit ?? 10)->get()
        ]);
    }

    public function getFiles(User $user)
    {
        return $user->files()->where('folder_id', null)->get();
    }

    public function getFolders(User $user)
    {
        return $user->folders()->where('parent_id', null)->get();
    }

    public function getTrash(User $user)
    {
        return $this->respondWithSuccess([
            'files' => File::onlyTrashed()->where('deleted_by', $user->id)->get(),
            'folders' => Folder::onlyTrashed()->where('deleted_by', $user->id)->get()
        ]);
    }
}

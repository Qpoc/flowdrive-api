<?php

namespace App\Http\Controllers;


use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use F9Web\ApiResponseHelpers;
use App\Http\Controllers\Controller;
use App\Services\FolderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class FolderController extends Controller
{
    use ApiResponseHelpers;

    public function store(Request $request)
    {
        $request->validate([
            'folder_name' => 'required|string|max:255',
        ]);

        $user = auth()->user();

        $folderName = FolderService::createFoldername($request->parent_folder_id, $request->folder_name);

        $user->folders()->create([
            'name' => $folderName,
            'parent_id' => $request->parent_folder_id
        ]);

        return $this->respondCreated([
            'message' => 'Folder created successfully'
        ]);
    }

    public function destroy(Request $request)
    {
        $folderIds = $request->input('folder_ids');

        if (empty($folderIds)) {
            return response()->json(['message' => 'No folder IDs provided'], 400);
        }

        // Retrieve the list of files the user owns
        $userFolderIds = Folder::whereIn('id', $folderIds)
            ->where('created_by', Auth::id())
            ->pluck('id') // Get only the IDs of the files the user owns
            ->toArray();

        // Find any file_id that the user does not own
        $unauthorizedFolderIds = array_diff($folderIds, $userFolderIds);

        if (count($unauthorizedFolderIds) > 0) {
            return response()->json(['message' => 'Unauthorized to delete the following folders: ' . implode(', ', $unauthorizedFolderIds)], 403);
        }

        Folder::whereIn('id', $userFolderIds)->update([
            'deleted_by' => Auth::id()
        ]);
        // Now delete all files that the user owns
        Folder::whereIn('id', $userFolderIds)->delete();

        return response()->json(['message' => 'Folders deleted successfully']);
    }

    public function move(Request $request)
    {
        Folder::whereIn('id', $request->folder_ids)->update(['parent_id' => $request->folder_id]);
        File::whereIn('id', $request->file_ids)->update(['folder_id' => $request->folder_id]);

        return $this->respondWithSuccess([
            'message' => 'Folder(s)/File(s) moved successfully'
        ]);
    }

    public function getFolders(Folder $folder)
    {
        return Folder::where('parent_id', $folder->id)->get();
    }

    public function getFiles(Folder $folder)
    {
        return File::where('folder_id', $folder->id)->get();
    }

    public function show(Folder $folder)
    {
        return $this->respondWithSuccess($folder);
    }

    public function rename(Folder $folder, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'regex:/^[^<>:;,?"*|\\/\.]+$/', // Custom regex to exclude invalid characters
                'max:255' // Optional: Limit the length of the file name
            ]
        ]);

        if ($validator->fails()) {
            return $this->respondFailedValidation("Invalid folder name");
        }

        $folderName = FolderService::createFoldername($folder->parent_id, $request->name);

        $folder->update([
            'name' => $folderName
        ]);

        return $this->respondWithSuccess([
            'message' => 'Folder renamed successfully',
            'data' => $folder
        ]);
    }

    public function breadcrumbs(Folder $folder)
    {
        return $folder->getBreadcrumbs();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use F9Web\ApiResponseHelpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class TrashController extends Controller
{
    use ApiResponseHelpers;

    public function destroyFiles(Request $request)
    {
        $fileIds = $request->input('file_ids'); // Receive array of file IDs

        if (empty($fileIds)) {
            return response()->json(['message' => 'No file IDs provided'], 400);
        }

        // Retrieve the files that are marked for deletion
        $filesToDelete = File::whereIn('id', $fileIds)->onlyTrashed()->get();

        if ($filesToDelete->isEmpty()) {
            return response()->json(['message' => 'No files found for the given IDs'], 404);
        }

        // Loop through each file to delete it
        foreach ($filesToDelete as $file) {
            // Delete the physical file from storage
            Storage::disk('local')->delete("$file->path");

            // Perform the force delete (permanently delete from database)
            $file->forceDelete();
        }

        return $this->respondWithSuccess($filesToDelete);
    }

    public function destroyFolders(Request $request)
    {
        $folderIds = $request->input('folder_ids'); // Receive array of folder IDs

        if (empty($folderIds)) {
            return response()->json(['message' => 'No folder IDs provided'], 400);
        }

        // Retrieve the folders that are marked for deletion
        $foldersToDelete = Folder::whereIn('id', $folderIds)->onlyTrashed()->get();

        if ($foldersToDelete->isEmpty()) {
            return response()->json(['message' => 'No folders found for the given IDs'], 404);
        }

        // Loop through each folder to delete it
        foreach ($foldersToDelete as $folder) {
            // Perform the force delete (permanently delete from database)
            $folder->forceDeleteFiles();
            $folder->forceDelete();
        }

        return $this->respondWithSuccess($foldersToDelete);
    }

    public function restore(File $trashFile)
    {
        $trashFile->restore();
        return $this->respondWithSuccess($trashFile);
    }
}

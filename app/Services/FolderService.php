<?php
namespace App\Services;

use App\Models\Folder;
use Illuminate\Support\Facades\Log;

class FolderService {

    public static function createFoldername($folderId = null, $folderName = null, $duplicate = 0)
    {
        $duplicatedFoldername = $folderName;

        Log::info($folderId);

        while ($isDuplicated = Folder::where([
            ['parent_id', $folderId],
            ['name', $duplicatedFoldername]
        ])->exists()) {
            if ($isDuplicated) {
                $duplicate++;
                $duplicatedFoldername = "{$folderName}_{$duplicate}";
            }
        }
        return $duplicatedFoldername;
    }

}

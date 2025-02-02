<?php
namespace App\Services;

use App\Models\File;

class FileService {

    public static function createFilename($folderId = null, $fileName = null, $extension = null, $duplicate = 0)
    {

        $fileName = pathinfo($fileName, PATHINFO_FILENAME);
        $fileNameWithExt = "{$fileName}.{$extension}";

        while ($isDuplicated = File::where([
            ['folder_id', $folderId],
            ['name', $fileNameWithExt]
        ])->exists()) {

            if ($isDuplicated) {
                $duplicate++;
                $duplicatedFilename = "{$fileName}_{$duplicate}";
                $fileNameWithExt = "{$duplicatedFilename}.{$extension}";
            } else {
                $fileName = "{$fileName}.{$extension}";
                $fileNameWithExt = "{$fileName}.{$extension}";
            }
        }

        return $fileNameWithExt;
    }

}

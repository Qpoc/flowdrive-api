<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use App\Services\FileService;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use App\Handler\FlowJSUploadHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Routing\Controller as BaseController;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Ramsey\Uuid\Rfc4122\UuidV4;

class UploadController extends BaseController
{
    /**
     * Handles the file upload
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws UploadMissingFileException
     * @throws UploadFailedException
     */
    public function upload(Request $request)
    {
        // create the file receiver
        $receiver = new FileReceiver("file", $request, FlowJSUploadHandler::class);

        // check if the upload is success, throw exception or return response you need
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        // receive the file
        $save = $receiver->receive();

        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            // save the file and return any response you need, current example uses `move` function. If you are
            // not using move, you need to manually delete the file by unlink($save->getFile()->getPathname())
            return $this->saveFile($save->getFile(), $request->flowTotalSize, $request->folder_id, $request->flowRelativePath);
        }

        // we are in chunk mode, lets send the current progress
        /** @var AbstractHandler $handler */
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            'status' => true
        ]);
    }

    /**
     * Saves the file to S3 server
     *
     * @param UploadedFile $file
     *
     * @return JsonResponse
     */
    protected function saveFileToS3($file)
    {
        $fileName = $this->createFilename($file);

        $disk = Storage::disk('s3');
        // It's better to use streaming Streaming (laravel 5.4+)
        $disk->putFileAs('photos', $file, $fileName);

        // for older laravel
        // $disk->put($fileName, file_get_contents($file), 'public');
        $mime = str_replace('/', '-', $file->getMimeType());

        // We need to delete the file when uploaded to s3
        unlink($file->getPathname());

        return response()->json([
            'path' => $disk->url($fileName),
            'name' => $fileName,
            'mime_type' => $mime
        ]);
    }

    /**
     * Saves the file
     *
     * @param UploadedFile $file
     *
     * @return JsonResponse
     */
    protected function saveFile(UploadedFile $file, $totalSize, $folderId = null, $flowRelativePath = null)
    {
        $folderId = $this->createDirectories($folderId, $flowRelativePath);
        $extension = $file->getClientOriginalExtension();
        $fileName = FileService::createFilename($folderId, str_replace("." . $extension, "", $file->getClientOriginalName()), $extension);
        // Group files by mime type
        $mime = str_replace('/', '-', $file->getMimeType());
        // Group files by the date (week
        $dateFolder = date("Y-m-W");

        // Build the file path
        $filePath = "upload/{$mime}/{$dateFolder}/";
        // $finalPath = storage_path("app/".$filePath);

        // move the file name
        $filePath = $file->storeAs($filePath, $fileName, 'local');

        File::create([
            'uuid' => UuidV4::uuid4(),
            'name' => $fileName,
            'folder_id' => $folderId,
            'path' => $filePath,
            'size' => $totalSize,
            'mime' => $mime,
            'created_by' => Auth::id()
        ]);

        return response()->json([
            'path' => $filePath,
            'name' => $fileName,
            'mime_type' => $mime
        ]);
    }

    private function createDirectories($parentId, $relativePath)
    {

        if (dirname($relativePath) == '.') {
            return $parentId;
        }

        $directories = explode('/', dirname($relativePath));
        foreach ($directories as $directory) {
            $folder = Folder::where([
                ['parent_id', $parentId],
                ['name', $directory]
            ])->first();

            if (!$folder) {
                $folder = Folder::create([
                    'name' => $directory,
                    'parent_id' => $parentId,
                    'created_by' => Auth::id()
                ]);
            }

            $parentId = $folder->id;
        }

        return $parentId;
    }

    public function callback(Request $request)
    {
        // Log the callback data for debugging (optional)
        Log::info('ONLYOFFICE callback data:', $request->all());

        // Validate the callback payload
        $data = $request->validate([
            'status' => 'required|integer',
            'key' => 'required|string',
            'url' => 'nullable|string', // Only present when the document is ready for download
            // Add other fields as necessary
        ]);

        // Check if the document editing is completed or ready for download
        if (in_array($data['status'], [2, 4])) {
            // If URL is provided, download and save the file
            if (!empty($data['url'])) {
                $this->downloadAndSaveFile($data['url'], $data['key']);
            }
        } elseif ($data['status'] === 6) {
            // Document saving error
            Log::error('ONLYOFFICE document saving error.', $data);
        } else {
            Log::info('Received ONLYOFFICE callback with status: ' . $data['status']);
        }

        // Respond with HTTP 200 to acknowledge receipt
        return response()->json(['error' => 0], Response::HTTP_OK);
    }

    /**
     * Download and save the file from ONLYOFFICE server.
     *
     * @param string $url
     * @param string $key
     * @return void
     */
    private function downloadAndSaveFile(string $url, string $key)
    {
        try {
            // Download the file
            $file = File::find(explode('-', $key)[0]);

            $file->uuid = UuidV4::uuid4();
            $file->save();

            $response = Http::get($url);

            if ($response->successful()) {
                // Save the file to storage (e.g., 'local' or 's3' storage disk)
                $storage = Storage::disk('local')->put("$file->path", $response->body());

                Log::info("ONLYOFFICE document saved as: {$storage}");
            } else {
                Log::error("Failed to download ONLYOFFICE document from URL: {$url}");
            }
        } catch (\Exception $e) {
            Log::error("Error while saving ONLYOFFICE document: " . $e->getMessage());
        }
    }
}

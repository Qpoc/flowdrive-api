<?php

namespace App\Http\Controllers;

use App\Models\File;
use ZipStream\ZipStream;
use App\Models\FileShare;
use Illuminate\Http\Request;
use App\Services\FileService;
use F9Web\ApiResponseHelpers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    use ApiResponseHelpers;

    public function index(Request $request)
    {
        $files = File::whereIn('id', $request->file_ids)->with(['sharedLink'])->get();

        return $this->respondWithSuccess([
            'message' => 'success',
            'data' => $files
        ]);
    }

    public function download(File $file)
    {
        if (!request()->hasValidSignature()) {
            abort(403, 'Unauthorized access');
        }

        $path = "$file->path";

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File not found');
        }

        return response()->stream(function () use ($path) {
            $stream = Storage::disk('local')->readStream($path);

            if ($stream) {
                while(!feof($stream)) {
                    echo fread($stream, 1024 * 8);
                    flush();
                }

                fclose($stream);
            }
        }, 200, [
            'Content-Type' => Storage::disk('local')->mimeType($path),
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    public function share(Request $request)
    {

        $fileShares = [];

        foreach ($request->file_ids as $file_id) {
            $fileShare = FileShare::updateOrCreate(
                [
                    'file_id' => $file_id
                ],
                [
                    'file_id' => $file_id,
                    'link_type' => $request->link_type,
                    'access_type' => $request->access_type,
                    'is_active' => true
                ]
            );

            $fileShare->link = URL::signedRoute('share.file.download', ['share' => $fileShare->id]);

            $fileShare->save();

            $fileShares[] = $fileShare;
        }

        return $this->respondWithSuccess([
            'message' => 'File shared successfully',
            'data' => $fileShares
        ]);
    }

    public function multipleDownload(Request $request)
    {
        return response()->stream(function () use ($request) {
            // create a new zipstream object
            $zip = new ZipStream(
                outputName: 'example.zip',

                // enable output of HTTP headers
                sendHttpHeaders: true,
            );

            $files = File::whereIn('id', $request->file_ids)->get();
            foreach ($files as $file) {
                $zip->addFileFromPath($file->name, Storage::disk('local')->path($file->path));
            }

            // finish the zip stream
            $zip->finish();
        });
    }

    public function show(File $file)
    {
        return $this->respondWithSuccess($file);
    }

    public function rename(File $file, Request $request)
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
            return $this->respondFailedValidation("Invalid file name");
        }

        $fileName = FileService::createFilename($file->folder_id, $request->name, explode('.', $file->name)[1]);

        $file->update([
            'name' => $fileName
        ]);

        return $this->respondWithSuccess([
            'message' => 'File renamed successfully',
            'data' => $file
        ]);
    }

    public function destroy(Request $request)
    {

        $fileIds = $request->input('file_ids');

        if (empty($fileIds)) {
            return response()->json(['message' => 'No file IDs provided'], 400);
        }

        // Retrieve the list of files the user owns
        $userFileIds = File::whereIn('id', $fileIds)
            ->where('created_by', Auth::id())
            ->pluck('id') // Get only the IDs of the files the user owns
            ->toArray();

        // Find any file_id that the user does not own
        $unauthorizedFileIds = array_diff($fileIds, $userFileIds);

        if (count($unauthorizedFileIds) > 0) {
            return response()->json(['message' => 'Unauthorized to delete the following files: ' . implode(', ', $unauthorizedFileIds)], 403);
        }

        File::whereIn('id', $userFileIds)->update([
            'deleted_by' => Auth::id()
        ]);
        // Now delete all files that the user owns
        File::whereIn('id', $userFileIds)->delete();

        return response()->json(['message' => 'Files deleted successfully']);
    }
}

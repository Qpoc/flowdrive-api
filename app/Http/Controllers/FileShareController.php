<?php

namespace App\Http\Controllers;

use App\Models\FileShare;
use F9Web\ApiResponseHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileShareController extends Controller
{
    use ApiResponseHelpers;
    public function show(FileShare $share) {
        if ($share->link_type != 1 || !$share->is_active) {
            return $this->respondNotFound('File not found');
        }

        $share = FileShare::with(['file'])->find($share->id);

        return $this->respondWithSuccess([
            'message' => 'success',
            'data' => $share
        ]);
    }
    public function download(FileShare $share) {
        if (!request()->hasValidSignatureWhileIgnoring(['verify'])) {
            abort(403, 'Unauthorized access');
        }

        if (!$share->is_active || !($share->link_type == 1)) {
            abort(404, 'File not found');
        }

        if (request()->verify == 1) {
            return response()->json([
                'success' => true,
            ]);
        }

        $file = $share->file;

        $path = "{$file->path}";

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File not found');
        }

        Log::info("Downloading file: {$path}");

        return response()->stream(function () use ($path) {
            echo Storage::disk('local')->get($path);
        }, 200, [
            'Content-Type' => Storage::disk('local')->mimeType($path),
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MoveController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\FileShareController;
use App\Http\Controllers\OnlyOfficeController;
use App\Http\Controllers\ShareController;

Route::group([
    'middleware' => 'api',
], function () {
    Route::prefix('v1')->group(function () {

        Route::prefix('onlyoffice')->group(function () {
            Route::post('token', [OnlyOfficeController::class, 'generateToken']);
            Route::get('signed-url/file/{file}', [OnlyOfficeController::class, 'getSignedUrl']);
        });

        Route::prefix('auth')->group(function () {
            Route::post('login', [AuthController::class, 'login']);
            Route::post('register', [AuthController::class, 'register']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });

        Route::prefix('user')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{user}/file', [UserController::class, 'getFiles'])->can('get-files', 'user');
            Route::get('/{user}/folder', [UserController::class, 'getFolders'])->can('get-folders', 'user');
            Route::get('/{user}/trash', [UserController::class, 'getTrash'])->can('get-trash', 'user');
        });

        Route::prefix('file')->group(function () {
            Route::post('/', [FileController::class, 'index'])->middleware('permission:can view file');
            Route::get('/{file}', [FileController::class, 'show'])->can('show', 'file')->middleware('permission:can view file');
            Route::patch('/{file}/rename', [FileController::class, 'rename'])->can('rename', 'file')->middleware('permission:can rename file');
            Route::get('/{file}/download', [FileController::class, 'download'])->name('file.download');
            Route::post('/delete', [FileController::class, 'destroy'])->middleware('permission:can delete file');
            Route::post('/download', [FileController::class, 'multipleDownload'])->middleware('permission:can download file');
            Route::post('/share', [FileController::class, 'share']);
        });

        Route::prefix('share')->group(function () {
            Route::post('/invite', [ShareController::class, 'invite']);
        });

        Route::prefix('folder')->group(function () {
            Route::post('/', [FolderController::class, 'store'])->middleware('permission:can create folder');
            Route::patch('/{folder}/rename', [FolderController::class, 'rename'])->can('rename', 'folder')->middleware('permission:can rename folder');
            Route::post('/delete', [FolderController::class, 'destroy'])->middleware('permission:can delete folder');

            Route::middleware('permission:can view folder')->group(function () {
                Route::get('/{folder}', [FolderController::class, 'show'])->can('show', 'folder');
                Route::get('/{folder}/folder', [FolderController::class, 'getFolders'])->can('show', 'folder');
                Route::get('/{folder}/file', [FolderController::class, 'getFiles'])->can('show', 'folder');
            });

            Route::post('/move', [FolderController::class, 'move'])->middleware('permission:can move file and folder');
            Route::get('/{folder}/breadcrumbs', [FolderController::class, 'breadcrumbs'])->can('show', 'folder');
        });

        Route::prefix('trash')->group(function () {
            Route::post('/file', [TrashController::class, 'destroyFiles'])->middleware('permission:can delete file');
            Route::post('/folder', [TrashController::class, 'destroyFolders'])->middleware('permission:can delete folder');
            Route::post('/file/{trashFile}/restore', [TrashController::class, 'restore'])->can('restore', 'trashFile');
        });

        Route::post('upload', [UploadController::class, 'upload'])->middleware('permission:can upload file');
    });
});



Route::prefix('v1')->group(function () {
    Route::post('/callback', [UploadController::class, 'callback']);

    Route::prefix('share')->group(function () {
        Route::get('/{share}', [FileShareController::class, 'show']);
        // Route::post('/file', [FileController::class, 'shareFile'])->middleware('permission:can share file');
        Route::get('/{share}/file/download', [FileShareController::class, 'download'])->name('share.file.download');
    });
});

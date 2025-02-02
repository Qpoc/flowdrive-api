<?php

namespace App\Http\Controllers;

use App\Models\FileAccess;
use F9Web\ApiResponseHelpers;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    use ApiResponseHelpers;
    public function invite(Request $request) {
        foreach ($request->invites as $invite) {
            foreach ($invite['file_ids'] as $fileId) {
                FileAccess::updateOrCreate([
                    'file_id' => $fileId,
                    'user_id' => $invite['user_id']
                ]);
            }
        }

        return $this->respondWithSuccess([
            'message' => 'Invites sent successfully'
        ]);
    }
}

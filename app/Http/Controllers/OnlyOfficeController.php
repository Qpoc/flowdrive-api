<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use F9Web\ApiResponseHelpers;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTFactory;

class OnlyOfficeController extends Controller
{

    use ApiResponseHelpers;

    public function generateToken(Request $request)
    {

        $claims = array_merge($request->config, ['iss' => 'onlyoffice', 'aud' => 'onlyoffice', 'iat' => time(), 'exp' => time() + 3600, 'sub' => 'onlyoffice', 'jti' => 'onlyoffice']);

        // Create a Payload object using JWTFactory
        $payload = JWTFactory::customClaims($claims)->make();
        // Encode the Payload object to generate the token
        return JWTAuth::encode($payload)->get();
    }

    public function getSignedUrl($file)
    {
        return $this->respondWithSuccess([
            'url' => URL::temporarySignedRoute('file.download', now()->addMinutes(5), ['file' => $file])
        ]);
    }
}

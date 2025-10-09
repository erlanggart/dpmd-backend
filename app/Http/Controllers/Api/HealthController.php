<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'OK',
            'message' => 'DPMD API is running',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'environment' => app()->environment()
        ]);
    }

    /**
     * System info endpoint
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => config('app.name'),
                'app_version' => '1.0.0',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'face_id_enabled' => true,
                'endpoints' => [
                    'face_register' => '/api/face/register',
                    'face_login' => '/api/face/login',
                    'face_status' => '/api/face/status'
                ]
            ]
        ]);
    }

    /**
     * Face ID system status
     */
    public function faceIdStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'face_id_enabled' => true,
                'models_required' => [
                    'tiny_face_detector_model',
                    'face_landmark_68_model', 
                    'face_recognition_model'
                ],
                'security_features' => [
                    'encryption' => true,
                    'liveness_detection' => true,
                    'anti_spoofing' => true,
                    'rate_limiting' => true
                ],
                'supported_browsers' => [
                    'Chrome 60+',
                    'Firefox 55+',
                    'Safari 11+',
                    'Edge 79+'
                ]
            ]
        ]);
    }
}

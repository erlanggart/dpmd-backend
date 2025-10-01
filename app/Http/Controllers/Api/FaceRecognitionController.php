<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFaceData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class FaceRecognitionController extends Controller
{
    /**
     * Register face data for authenticated user
     */
    public function registerFace(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'encrypted_descriptor' => 'required|string',
                'face_metadata' => 'required|array',
                'confidence_score' => 'required|numeric|between:0.6,1.0',
                'password' => 'required|string' // Confirm with password for security
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify password for additional security
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password verification failed'
                ], 403);
            }

            // Check if user already has face data
            $existingFaceData = UserFaceData::where('user_id', $user->id)->first();
            if ($existingFaceData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Face data already registered. Please delete existing data first.'
                ], 409);
            }

            // Generate unique face ID
            $faceId = UserFaceData::generateFaceId($user->id, $user->role);

            // Create face data record
            $faceData = UserFaceData::create([
                'user_id' => $user->id,
                'face_id' => $faceId,
                'encrypted_descriptor' => $request->encrypted_descriptor,
                'face_metadata' => $request->face_metadata,
                'confidence_score' => $request->confidence_score,
                'registration_ip' => $request->ip(),
                'registration_user_agent' => $request->userAgent(),
                'is_active' => true,
                'is_verified' => false // Requires admin verification
            ]);

            // Log registration activity
            Log::info('Face registration completed', [
                'user_id' => $user->id,
                'face_id' => $faceId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Face registration successful. Pending admin verification.',
                'data' => [
                    'face_id' => $faceId,
                    'is_verified' => false,
                    'created_at' => $faceData->created_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Face registration failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face registration failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Authenticate user with face recognition
     */
    public function faceLogin(Request $request): JsonResponse
    {
        try {
            // Rate limiting
            $key = 'face-login:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'success' => false,
                    'message' => "Too many face login attempts. Please try again in {$seconds} seconds."
                ], 429);
            }

            RateLimiter::hit($key, 300); // 5 minutes lockout

            // Validate request
            $validator = Validator::make($request->all(), [
                'face_descriptor' => 'required|string',
                'confidence_score' => 'required|numeric|between:0.6,1.0',
                'liveness_check' => 'required|boolean',
                'metadata' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid face data provided',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Liveness check validation
            if (!$request->liveness_check) {
                return response()->json([
                    'success' => false,
                    'message' => 'Liveness detection failed. Please try again with natural movements.'
                ], 403);
            }

            // Find matching face data
            $faceData = $this->findMatchingFace($request->face_descriptor, $request->confidence_score);

            if (!$faceData) {
                Log::warning('Face login failed - no match found', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Face not recognized or confidence too low'
                ], 401);
            }

            $user = $faceData->user;

            // Check if user account is active
            if (!$user || $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'User account is not active'
                ], 403);
            }

            // Update face data usage
            $faceData->recordUsage();

            // Create authentication token
            $token = $user->createToken('face-login-token', ['*'], now()->addDays(30))->plainTextToken;

            // Log successful login
            Log::info('Face login successful', [
                'user_id' => $user->id,
                'face_id' => $faceData->face_id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Clear rate limit on success
            RateLimiter::clear($key);

            return response()->json([
                'success' => true,
                'message' => 'Face login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ],
                    'token' => $token,
                    'expires_at' => now()->addDays(30)->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Face login error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face login failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Get user's face registration status
     */
    public function getFaceStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $faceData = UserFaceData::where('user_id', $user->id)->first();

            if (!$faceData) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_face_data' => false,
                        'is_verified' => false,
                        'is_active' => false
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_face_data' => true,
                    'is_verified' => $faceData->is_verified,
                    'is_active' => $faceData->is_active,
                    'registered_at' => $faceData->created_at,
                    'last_used_at' => $faceData->last_used_at,
                    'usage_count' => $faceData->usage_count,
                    'confidence_score' => $faceData->confidence_score
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get face status error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get face status'
            ], 500);
        }
    }

    /**
     * Delete user's face data
     */
    public function deleteFaceData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate password for security
            $validator = Validator::make($request->all(), [
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password required for verification'
                ], 422);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password verification failed'
                ], 403);
            }

            $faceData = UserFaceData::where('user_id', $user->id)->first();

            if (!$faceData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No face data found'
                ], 404);
            }

            $faceId = $faceData->face_id;
            $faceData->delete();

            // Log deletion
            Log::info('Face data deleted', [
                'user_id' => $user->id,
                'face_id' => $faceId,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Face data deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete face data error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete face data'
            ], 500);
        }
    }

    /**
     * Admin: Verify user's face data
     */
    public function verifyFaceData(Request $request, $userId): JsonResponse
    {
        try {
            $admin = Auth::user();

            // Check admin permissions
            if (!in_array($admin->role, ['superadmin', 'sekretariat'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $faceData = UserFaceData::where('user_id', $userId)->first();

            if (!$faceData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Face data not found'
                ], 404);
            }

            $faceData->verify();

            Log::info('Face data verified by admin', [
                'admin_id' => $admin->id,
                'user_id' => $userId,
                'face_id' => $faceData->face_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Face data verified successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Verify face data error', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify face data'
            ], 500);
        }
    }

    /**
     * Find matching face from database
     */
    private function findMatchingFace(string $faceDescriptor, float $confidence): ?UserFaceData
    {
        // Get all active and verified face data
        $allFaceData = UserFaceData::active()->verified()->get();

        $bestMatch = null;
        $bestSimilarity = 0;
        $threshold = 0.6; // Minimum similarity threshold

        foreach ($allFaceData as $storedFaceData) {
            try {
                // This would require implementing face comparison on backend
                // For now, we'll use a simplified approach
                // In production, consider using a dedicated face comparison service
                
                $similarity = $this->calculateFaceSimilarity(
                    $faceDescriptor, 
                    $storedFaceData->encrypted_descriptor
                );

                if ($similarity > $threshold && $similarity > $bestSimilarity) {
                    $bestSimilarity = $similarity;
                    $bestMatch = $storedFaceData;
                }
            } catch (\Exception $e) {
                Log::warning('Face comparison failed', [
                    'face_id' => $storedFaceData->face_id,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate face similarity (simplified implementation)
     */
    private function calculateFaceSimilarity(string $descriptor1, string $descriptor2): float
    {
        // This is a simplified implementation
        // In production, you should use proper face comparison algorithms
        // or integrate with dedicated face recognition services
        
        try {
            // For now, return a mock similarity based on descriptor comparison
            // You would implement actual euclidean distance calculation here
            return 0.85; // Mock high similarity for development
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}

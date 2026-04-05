<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use OpenApi\Attributes as OA;

class ApiSocialAuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/google',
        summary: 'Sign in / register with a Google OAuth access token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SocialAuthRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Login successful',   content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
            new OA\Response(response: 400, description: 'Token missing/invalid'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function googleSignIn(Request $request)
    {
        try {
            $access_token = $request->input('access_token');

            if (!$access_token) {
                return response()->json(['status' => 'error', 'message' => 'Access token is required.'], 400);
            }

            $googleUser = Socialite::driver('google')->userFromToken($access_token);

            if (!$googleUser) {
                return response()->json(['status' => 'error', 'message' => 'Google user not found.'], 400);
            }

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update(['avatar' => $googleUser->getAvatar(), 'google_id' => $googleUser->getId()]);
            } else {
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password'  => bcrypt(Str::random(16)),
                    'avatar'    => $googleUser->getAvatar(),
                    'role_id'   => 0,
                ]);
            }

            $token = $user->createToken('Auth-Api')->plainTextToken;

            return response()->json(['status' => 'success', 'message' => 'Google login successful.', 'uuid' => $user->id, 'token' => $token]);
        } catch (Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Error while signing in.', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/facebook',
        summary: 'Sign in / register with a Facebook OAuth access token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SocialAuthRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Login successful',   content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
            new OA\Response(response: 400, description: 'Token missing/invalid'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function facebookSignIn(Request $request)
    {
        $request->validate(['access_token' => 'required|string']);

        try {
            $access_token = $request->input('access_token');

            if (!$access_token) {
                return response()->json(['status' => 'error', 'message' => 'Access token is required.'], 400);
            }

            $facebookUser = Socialite::driver('facebook')->userFromToken($access_token);

            if (!$facebookUser) {
                return response()->json(['status' => 'error', 'message' => 'Facebook user not found.'], 400);
            }

            $user = User::where('email', $facebookUser->getEmail())->first();

            if ($user) {
                $user->update(['avatar' => $facebookUser->getAvatar(), 'facebook_id' => $facebookUser->getId()]);
            } else {
                $user = User::create([
                    'name'        => $facebookUser->getName(),
                    'email'       => $facebookUser->getEmail(),
                    'facebook_id' => $facebookUser->getId(),
                    'password'    => bcrypt(Str::random(16)),
                    'avatar'      => $facebookUser->getAvatar(),
                    'role_id'     => 0,
                ]);
            }

            $token = $user->createToken('Auth-Api')->plainTextToken;

            return response()->json(['status' => 'success', 'message' => 'Facebook login successful.', 'uuid' => $user->id, 'token' => $token], 200);
        } catch (Exception $e) {
            Log::error('Facebook login error: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Error while signing in.', 'error' => $e->getMessage()], 500);
        }
    }
}

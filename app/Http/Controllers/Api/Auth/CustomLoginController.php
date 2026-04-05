<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Requests\Api\ApiRegisterRequest;
use App\Models\TempCredential;
use App\Models\User;
use App\Mail\VerificationCodeMail;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class CustomLoginController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Log in with email and password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Login successful',   content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
            new OA\Response(response: 401, description: 'Invalid password',   content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'User not found',     content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed',  content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function login(ApiLoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'The user with this email not found', 'status' => 'error'], 404);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid password', 'status' => 'error'], 401);
        }

        $token = $user->createToken('Auth-Api')->plainTextToken;

        return response()->json(['uuid' => $user->id, 'status' => 'success', 'message' => 'Successfully logged in', 'token' => $token], 200);
    }

    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'Register a new account with email and password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'Registered successfully', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
            new OA\Response(response: 409, description: 'Email already taken',     content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed',       content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function register(ApiRegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            return response()->json(['message' => 'User with such email already exists'], 409);
        }

        $createdUser = User::create([
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id'  => 2,
        ]);

        $token = $createdUser->createToken('Auth-Api')->plainTextToken;

        return response()->json(['token' => $token, 'uuid' => $createdUser->id, 'status' => 'success', 'message' => 'successfully logged in'], 201);
    }

    #[OA\Post(
        path: '/api/v1/auth/send-verification-code',
        summary: 'Send an 8-character verification code to the given email',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SendVerificationRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Code sent',          content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 422, description: 'Validation failed',  content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 500, description: 'Mail/storage error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function sendVerificationCode(Request $request)
    {
        Log::debug('Received request to send verification code', ['email' => $request->input('email')]);

        $validator = Validator::make($request->all(), ['email' => 'required|email|max:255']);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $email      = $request->input('email');
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%&';
        $password   = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $expiresAt = now()->addMinutes(15);

        try {
            TempCredential::updateOrCreate(['email' => $email], ['password' => $password, 'expires_at' => $expiresAt]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store verification code', 'error' => $e->getMessage()], 500);
        }

        try {
            Mail::to($email)->send(new VerificationCodeMail($password, $email));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send email', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Verification code sent successfully'], 200);
    }

    #[OA\Post(
        path: '/api/v1/auth/verify-and-register',
        summary: 'Verify the emailed code and create an account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/VerifyAndRegisterRequest')
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 201, description: 'Registered successfully', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
            new OA\Response(response: 401, description: 'Wrong code',              content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Email not found',         content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Email already in use',    content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 410, description: 'Code expired',            content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed',       content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function verifyAndRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|max:255',
            'password' => 'required|string|size:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Неправильный пароль или email', 'errors' => $validator->errors()], 422);
        }

        $email         = $request->input('email');
        $password      = $request->input('password');
        $tempCredential = TempCredential::where('email', $email)->first();

        if (!$tempCredential) {
            return response()->json(['message' => 'Данный email не был найден'], 404);
        }

        if ($tempCredential->password !== $password) {
            return response()->json(['message' => 'Введен неправильный сгенерированный пароль'], 401);
        }

        if (now()->greaterThan($tempCredential->expires_at)) {
            return response()->json(['message' => 'Данный пароль истек'], 410);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Данный email уже испльзуется'], 409);
        }

        try {
            $newUser = User::create(['email' => $email, 'password' => Hash::make($password), 'role_id' => '0']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create user', 'error' => $e->getMessage()], 500);
        }

        $token = $newUser->createToken('Auth-Api')->plainTextToken;

        try {
            $tempCredential->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete temp credential', ['error' => $e->getMessage()]);
        }

        return response()->json(['uuid' => $newUser->id, 'status' => 'success', 'message' => 'Успешно зарегестрированы!', 'token' => $token], 201);
    }

    #[OA\Post(
        path: '/api/v1/store-fcm-token',
        summary: 'Store FCM push token for a user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/FcmTokenRequest')
        ),
        tags: ['Users'],
        responses: [
            new OA\Response(response: 200, description: 'Token stored', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function storeFcmToken(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id', 'fcm_token' => 'required|string']);

        try {
            $user            = User::findOrFail($request->user_id);
            $user->fcm_token = $request->fcm_token;
            $user->save();

            return response()->json(['message' => 'FCM token updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update FCM token', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/clear-fcm-token',
        summary: 'Clear the FCM push token for a user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [new OA\Property(property: 'user_id', type: 'integer', example: 42)]
            )
        ),
        tags: ['Users'],
        responses: [
            new OA\Response(response: 200, description: 'Token cleared'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function clearFcmToken(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        try {
            $user            = User::findOrFail($request->user_id);
            $user->fcm_token = null;
            $user->save();

            return response()->json(['message' => 'FCM token cleared successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to clear FCM token', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/user/{id}',
        summary: 'Get user name by ID',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User name', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'name', type: 'string', example: 'John Doe')]
            )),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json(['name' => $user->name], 200);
    }

    #[OA\Get(
        path: '/api/v1/user/{id}/fcm-token',
        summary: 'Get FCM token for a user',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'FCM token', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'fcm_token', type: 'string', nullable: true)]
            )),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getFcmToken($id)
    {
        $user = User::findOrFail($id);

        return response()->json(['fcm_token' => $user->fcm_token], 200);
    }
}

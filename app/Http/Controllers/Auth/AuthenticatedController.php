<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthenticatedController extends Controller
{
    private $tokenName = 'token';
    private $bearTokenPrefix = 'Bearer ';

    private $AuthService;

    public function __construct(AuthService $AuthService)
    {
        $this->AuthService = $AuthService;
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response(null, Response::HTTP_UNAUTHORIZED);
        }

        return response()->json(
            [
                'token' => $this->bearTokenPrefix . $user->createToken($this->tokenName)->plainTextToken
            ],
            Response::HTTP_OK
        );
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function user()
    {
        return response()->json(['user' => auth()->user()], Response::HTTP_OK);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|confirmed'
        ]);

        $this->AuthService->register($request->all(), $request->file('avatar'));
        return response(null, Response::HTTP_OK);
    }
}

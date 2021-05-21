<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where("email", $request->email)->firstOrFail();

        if (!Hash::check($request->password, $user->password)) {
            abort(401);
        }

        return response()->json([
            "user" => $user,
            "token" => auth("api")->login($user),
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create(
            [
                "name" => $request->name,
                "last_name" => $request->last_name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
            ]
        );

        return response()->json(
            [
                "user" => $user,
                "token" => auth("api")->login($user),
            ]
        );
    }

    public function me()
    {
        $user = auth("api")->user();
        $token = auth("api")->login($user);

        return response()->json([
            "user" => $user,
            "token" => $token
        ]);
    }
}

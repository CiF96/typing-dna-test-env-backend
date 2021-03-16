<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckTypingPatternRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ApiAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where("email", $request->email)->firstOrFail();

        if (!Hash::check($request->password, $user->password)) {
            abort(401);
        }

        $typingDnaResponse = $this->checkTypingPattern($user, $request->typing_pattern, $request->device_type, $request->pattern_type);

        // if ($typingDnaResponse["enrollment"] === 0 && $typingDnaResponse["result"] === 0) {
        //     abort(403);
        // }

        $numberOfUserPatterns = $user->typingPatterns()->where("device_type", $request->device_type)->count();

        $enrollmentsLeft = 3 - $numberOfUserPatterns > 0 ?  3 - $numberOfUserPatterns : 0;

        return response()->json([
            "user" => $user,
            "token" => auth("api")->login($user),
            "typing_dna" => $typingDnaResponse,
            "enrollments_left" => $enrollmentsLeft,
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            "name" => $request->name,
            "last_name" => $request->last_name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
        ]);

        $typingDnaResponse = $this->checkTypingPattern($user, $request->typing_pattern, $request->device_type, $request->pattern_type);

        $numberOfUserPatterns = $user->typingPatterns()->where("device_type", $request->device_type)->count();

        $enrollmentsLeft = 3 - $numberOfUserPatterns > 0 ?  3 - $numberOfUserPatterns : 0;

        return response()->json([
            "user" => $user,
            "token" => auth("api")->login($user),
            "typing_dna" => $typingDnaResponse,
            "enrollments_left" => $enrollmentsLeft,
        ]);
    }

    public function getTypingPatternData(CheckTypingPatternRequest $request)
    {
        $this->checkTypingPattern($request->user, $request->typing_pattern, $request->device_type, $request->pattern_type);
    }

    private function checkTypingPattern(User $user, string $typing_pattern, string $device_type, string $pattern_type)
    {


        $data = Http::withHeaders([
            "Accept" => "application/json",
        ])
            ->asForm()
            ->withBasicAuth("2e7491e341b0e45df007a4b518da7264", "e42ba14f1c4f2a201370eff85e1eaad1")
            ->post("https://api.typingdna.com/auto/" . $user->email, [
                "tp" => $typing_pattern
            ]);


        if ($data["status"] !== 200) {
            abort($data["status"], $data["message"]);
        }

        $user->typingPatterns()->create([
            "device_type" => $device_type,
            "pattern_type" => $pattern_type
        ]);

        return $data->json();
    }

    public function me()
    {
        return response()->json([
            "user" => auth("api")->user()
        ]);
    }
}

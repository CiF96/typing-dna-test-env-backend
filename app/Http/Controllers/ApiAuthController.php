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

        $typingDnaResponse = $this->checkTypingPattern($user->id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

        // if ($typingDnaResponse["enrollment"] === 0 && $typingDnaResponse["result"] === 0) {
        //     abort(403);
        // }

        $numberOfUserPatterns = $user->typingPatterns()->where([
            ["device_type", "=", $request->device_type],
            ["pattern_type", "=", $request->pattern_type],
            ["text_Id", "=", $request->text_id],
        ])->count();

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

        $typingDnaResponse = $this->checkTypingPattern($user->id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

        $numberOfUserPatterns = $user->typingPatterns()->where([
            ["device_type", $request->device_type],
            ["pattern_type", $request->pattern_type],
            ["text_Id", $request->text_id],
        ])->count();

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

        $typingDnaResponse = $this->checkTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

        $user = User::where("id", $request->user_id)->firstOrFail();

        $userData = Http::withHeaders([
            "Accept" => "application/json",
        ])
            ->asForm()
            ->withBasicAuth("2e7491e341b0e45df007a4b518da7264", "e42ba14f1c4f2a201370eff85e1eaad1")
            ->get("https://api.typingdna.com/user/" . $user->email, $request->pattern_type === "0" ? [
                "type" => $request->pattern_type,
            ] : [
                "type" => $request->pattern_type,
                "text_id" => $request->text_id
            ])->json();

        if ($request->pattern_type === "0") {
            $numberOfUserPatterns = $user->typingPatterns()->where([
                ["device_type", $request->device_type],
                ["pattern_type", $request->pattern_type],
            ])->count();
        } else {
            $numberOfUserPatterns = $user->typingPatterns()->where([
                ["device_type", $request->device_type],
                ["pattern_type", $request->pattern_type],
                ["text_Id", $request->text_id],
            ])->count();
        }
        $enrollmentsLeft = 3 - $numberOfUserPatterns > 0 ?  3 - $numberOfUserPatterns : 0;
        return response()->json([
            "typing_dna" => $typingDnaResponse,
            "enrollments_left" => $enrollmentsLeft,
            "user_data" => $userData,
        ]);
    }


    public function getTypingPatternDataPremium(CheckTypingPatternRequest $request)
    {
        $patternType = $request->pattern_type;

        $userData = $this->checkUser($request->user_id, $request->pattern_type, $request->text_id);

        $patternCount = $request->device_type === "mobile" ? $userData["mobilecount"] : $userData["count"];

        if ($patternCount < 3) {
            $enrollmentsLeft = 2 - $patternCount;
            $saveTypingPatternResponse = $this->saveTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

            return response()->json([
                "typing_dna" => $saveTypingPatternResponse->json(),
                "enrollments_left" => $enrollmentsLeft,
                // "user_data" => $userData,
            ]);
        }

        $verifyTypingPatternResponse = $this->verifyTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

        $messageCode = $verifyTypingPatternResponse["message_code"];
        $result = $verifyTypingPatternResponse["result"];
        $comparedPatterns =  $patternType === "0" ? $verifyTypingPatternResponse["previous_samples"] ?? null :  $verifyTypingPatternResponse["compared_samples"] ?? null;

        if ($comparedPatterns) {
            if ($comparedPatterns >= 3) {
                $enrollmentsLeft = 0;
            } else {
                $enrollmentsLeft = 3 - $comparedPatterns;
            }
        } else {
            $enrollmentsLeft = 2;
        }

        if (!$result) {
            return;
        }

        // if ($messageCode === 1 && $result === 1) {
        //     $this->saveTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);
        // }

        // if ($messageCode === 3 && $result === 0) {
        //     $this->saveTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);
        // }
        if ($messageCode === 1 && $result === 0) {
            // $this->saveTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);
            abort(403);
        }

        $this->saveTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

        return response()->json([
            "typing_dna" => $verifyTypingPatternResponse->json(),
            "enrollments_left" => $enrollmentsLeft,
            // "user_data" => $userData,
        ]);
    }

    private function checkTypingPattern(string $userId, string $typing_pattern, string $device_type, string $pattern_type, string $text_id)
    {

        $user = User::where("id", $userId)->firstOrFail();

        $data = Http::withHeaders([
            "Accept" => "application/json",
        ])
            ->asForm()
            ->withBasicAuth("2e7491e341b0e45df007a4b518da7264", "e42ba14f1c4f2a201370eff85e1eaad1")
            ->post("https://api.typingdna.com/auto/" . $user->email, [
                "tp" => $typing_pattern, "custom_field" => $text_id
            ]);
        // ->post("https://api.typingdna.com/verify/" . $user->email, [


        if ($data["status"] !== 200) {
            abort($data["status"], $data["message"]);
        }

        $user->typingPatterns()->create([
            "device_type" => $device_type,
            "pattern_type" => $pattern_type,
            "text_id" => $text_id
        ]);
        // TODO: dodaj is_successful field - razlikovanje izmedu uspjesnih i neuspjesnih login (neuspjensi se nesmiju brojati za enrollment?  )
        // TODO: napravi poziv prema check user za odredeni text_id i pattern_type umjesto brojanja postojecih patterna u backendu 
        return $data->json();
    }

    private function checkUser(string $userId, string $patternType, string $textId)
    {
        $user = User::where("id", $userId)->firstOrFail();

        $userData = Http::withHeaders([
            "Accept" => "application/json",
        ])
            ->asForm()
            ->withBasicAuth("2e7491e341b0e45df007a4b518da7264", "e42ba14f1c4f2a201370eff85e1eaad1")
            ->get("https://api.typingdna.com/user/" . $user->email, $patternType === "0" ? [
                "type" => $patternType,
            ] : [
                "type" => $patternType,
                "text_id" => $textId
            ]);

        return $userData;
    }

    private function saveTypingPattern(string $userId, string $typing_pattern, string $device_type, string $pattern_type, string $text_id)
    {

        $user = User::where("id", $userId)->firstOrFail();

        $data = Http::withHeaders([
            "Accept" => "application/json",
        ])
            ->asForm()
            ->withBasicAuth("2e7491e341b0e45df007a4b518da7264", "e42ba14f1c4f2a201370eff85e1eaad1")
            ->post("https://api.typingdna.com/save/" . $user->email, [
                "tp" => $typing_pattern, "custom_field" => $text_id
            ]);
        // ->post("https://api.typingdna.com/auto/" . $user->email, [


        if ($data["status"] !== 200) {
            abort($data["status"], $data["message"]);
        }

        $user->typingPatterns()->create([
            "device_type" => $device_type,
            "pattern_type" => $pattern_type,
            "text_id" => $text_id
        ]);

        return $data;
    }

    private function verifyTypingPattern(string $userId, string $typing_pattern, string $device_type, string $pattern_type, string $text_id)
    {

        $user = User::where("id", $userId)->firstOrFail();

        $data = Http::withHeaders([
            "Accept" => "application/json",
        ])
            ->asForm()
            ->withBasicAuth("2e7491e341b0e45df007a4b518da7264", "e42ba14f1c4f2a201370eff85e1eaad1")
            ->post("https://api.typingdna.com/verify/" . $user->email, [
                "tp" => $typing_pattern, "custom_field" => $text_id
            ]);
        // ->post("https://api.typingdna.com/auto/" . $user->email, [


        if ($data["status"] !== 200) {
            abort($data["status"], $data["message"]);
        }

        $user->typingPatterns()->create([
            "device_type" => $device_type,
            "pattern_type" => $pattern_type,
            "text_id" => $text_id
        ]);
        // TODO: dodaj is_successful field - razlikovanje izmedu uspjesnih i neuspjesnih login (neuspjensi se nesmiju brojati za enrollment?  )
        // TODO: napravi poziv prema check user za odredeni text_id i pattern_type umjesto brojanja postojecih patterna u backendu 
        return $data;
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

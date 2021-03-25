<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckTypingPatternRequest;
use App\Http\Requests\DeleteUserTypingPatternsRequest;
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

        return response()->json([
            "user" => $user,
            "token" => auth("api")->login($user),
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

        return response()->json([
            "user" => $user,
            "token" => auth("api")->login($user),
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
        $user = User::where("id", $request->user_id)->firstOrFail();

        $userData = $this->checkUser($request->user_id, $request->pattern_type, $request->text_id);

        $patternCount = $request->device_type === "mobile" ? $userData["mobilecount"] : $userData["count"];



        if ($patternCount < 3) {
            $enrollmentsLeft = 2 - $patternCount;

            if ($request->device_type === "mobile") {
                if ($request->selected_position !== $request->enrolled_position) {
                    abort(406, `Incorrect mobile position used`);
                }
            }

            $saveTypingPatternResponse = $this->saveTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

            return response()->json([
                "typing_dna" => $saveTypingPatternResponse->json(),
                "enrollments_left" => $enrollmentsLeft,
                // "user_data" => $userData,
            ]);
        }

        $verifyTypingPatternResponse = $this->verifyTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);

        $messageCode = $verifyTypingPatternResponse["message_code"];
        $result = $verifyTypingPatternResponse["result"] ?? null;

        if ($messageCode === 1 && ($result === 0 || $result === null)) {
            $user->typingPatterns()->create([
                "user_email" => $user->email,
                "device_type" => $request->device_type,
                "pattern_type" => $request->pattern_type,
                "text_id" => $request->text_id,
                "compared_samples" => $verifyTypingPatternResponse['compared_samples'] ?? null,
                "previous_samples" => $verifyTypingPatternResponse['previous_samples'],
                "confidence" => $verifyTypingPatternResponse['confidence'],
                "score" => $verifyTypingPatternResponse['score'],
                "net_score" => $verifyTypingPatternResponse['net_score'],
                "result" => $verifyTypingPatternResponse['result'],
                "success" => $verifyTypingPatternResponse['success'],
                "message_code" => $verifyTypingPatternResponse['message_code'],
                "position" => $verifyTypingPatternResponse['positions'][0] ?? null,
                "enrolled_position" => $request->enrolled_position ?? null,
                "selected_position" => $request->selected_position ?? null,
                "custom_field" => $verifyTypingPatternResponse['custom_field'] ?? null,
            ]);
            abort(403, "No match. This is probably not you.");
        }

        if ($messageCode === 3 && ($result === 0 || $result === null)) {


            $user->typingPatterns()->create([
                "user_email" => $user->email,
                "device_type" => $request->device_type,
                "pattern_type" => $request->pattern_type,
                "text_id" => $request->text_id,
                "compared_samples" => $verifyTypingPatternResponse['compared_samples'] ?? null,
                "previous_samples" => $verifyTypingPatternResponse['previous_samples'],
                "confidence" => $verifyTypingPatternResponse['confidence'],
                "score" => $verifyTypingPatternResponse['score'],
                "net_score" => $verifyTypingPatternResponse['net_score'],
                "result" => $verifyTypingPatternResponse['result'],
                "success" => $verifyTypingPatternResponse['success'],
                "message_code" => $verifyTypingPatternResponse['message_code'],
                "position" => $verifyTypingPatternResponse['positions'][0] ?? null,
                "enrolled_position" => $request->enrolled_position ?? null,
                "selected_position" => $request->selected_position ?? null,
                "custom_field" => $verifyTypingPatternResponse['custom_field'] ?? null,
            ]);

            abort(404, "This position is not enrolled!");
        }

        if ($messageCode === 1 && $result === 1) {
            $user->typingPatterns()->create([
                "user_email" => $user->email,
                "device_type" => $request->device_type,
                "pattern_type" => $request->pattern_type,
                "text_id" => $request->text_id,
                "compared_samples" => $verifyTypingPatternResponse['compared_samples'] ?? null,
                "previous_samples" => $verifyTypingPatternResponse['previous_samples'],
                "confidence" => $verifyTypingPatternResponse['confidence'],
                "score" => $verifyTypingPatternResponse['score'],
                "net_score" => $verifyTypingPatternResponse['net_score'],
                "result" => $verifyTypingPatternResponse['result'],
                "success" => $verifyTypingPatternResponse['success'],
                "message_code" => $verifyTypingPatternResponse['message_code'],
                "position" => $verifyTypingPatternResponse['positions'][0] ?? null,
                "enrolled_position" => $request->enrolled_position ?? null,
                "selected_position" => $request->selected_position ?? null,
                "custom_field" => $verifyTypingPatternResponse['custom_field'] ?? null,
            ]);
            $this->saveTypingPattern($request->user_id, $request->typing_pattern, $request->device_type, $request->pattern_type, $request->text_id);
            return response()->json([
                "typing_dna" => $verifyTypingPatternResponse->json(),
                "enrollments_left" => 0,
                // "user_data" => $userData,
            ]);
        }


        return response()->json([
            "typing_dna" => $verifyTypingPatternResponse->json(),
            "enrollments_left" => 0,
            "user_data" => $userData->json(),
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

    public function deleteUserTypingPatterns(DeleteUserTypingPatternsRequest $request)
    {
        $user = User::where("id", $request->user_id)->firstOrFail();

        $deleteUserPatternsData = Http::withHeaders([
            "Accept" => "application/json",
        ])
            ->asForm()
            ->withBasicAuth("2e7491e341b0e45df007a4b518da7264", "e42ba14f1c4f2a201370eff85e1eaad1")
            ->delete("https://api.typingdna.com/user/" . $user->email, [
                "device" => $request->device,
            ]);

        return response()->json([
            "typing_dna" => $deleteUserPatternsData->json(),

        ]);
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


        if ($data["status"] !== 200) {
            abort($data["status"], $data["message"]);
        }



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


        if ($data["status"] !== 200) {
            abort($data["status"], $data["message"]);
        }


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

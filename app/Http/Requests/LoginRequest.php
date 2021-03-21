<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "email" => ['required', "email", "max:50"],
            "password" => ['required', "string", "min:6"],
            "typing_pattern" => ['required', "string"],
            "device_type" => ['required', Rule::in(['mobile', 'desktop'])],
            "pattern_type" => ['required', Rule::in(['0', '1', '2'])],
            "text_id" => ['required', 'string']
        ];
    }
}

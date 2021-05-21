<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CheckTypingPatternRequest extends FormRequest
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
            "user_id" => ['required'],
            "typing_pattern" => ['nullable'],
            "device_type" => ['required', Rule::in(['mobile', 'desktop'])],
            "pattern_type" => ['required', Rule::in(['0', '1', '2'])],
            "text_id" => ['required', 'string'],
            "enrolled_position" => ['nullable', 'numeric', Rule::in([0, 1, 2, 3, 4, 5, 6])],
            "selected_position" => ['nullable', 'numeric', Rule::in([0, 1, 2, 3, 4, 5, 6])],
            "text_length" => ['nullable', 'string', Rule::in(['short', 'medium', 'default', 'long', 'veryLong'])],
            "experiment_type" => ['nullable', 'string', Rule::in(['default', 'length'])],
            "keyboard_type" => ['nullable', 'string', Rule::in(['tap', 'swipe', 'other'])]
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteUserTypingPatternsRequest extends FormRequest
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
            "user_id" => ['required', 'string'],
            "device" => ['required', Rule::in(['mobile', 'desktop', 'all'])],
            "pattern_type" => ['nullable', Rule::in(['0', '1', '2', 'all'])]
        ];
    }
}

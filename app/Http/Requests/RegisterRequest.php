<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            "name" => ['required', 'string', 'max:50'],
            "last_name" => ['required', 'string', 'max:50'],
            "email" => ['required', 'email', 'max:50', 'unique:' . User::class],
            "password" => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}

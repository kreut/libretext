<?php

namespace App\Http\Requests;

use App\Rules\IsValidTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class Profile extends FormRequest
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
        $rules = [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' =>   'required|email|unique:users,email,'.Auth::user()->id
        ];

        $rules['time_zone'] = new IsValidTimezone();
       return $rules;
    }
}

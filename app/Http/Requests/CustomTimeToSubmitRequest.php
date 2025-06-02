<?php

namespace App\Http\Requests;

use App\Rules\IsValidPeriodOfTime;
use Illuminate\Foundation\Http\FormRequest;

class CustomTimeToSubmitRequest extends FormRequest
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
            'time_to_submit' => ['required', new IsValidPeriodOfTime()]
        ];
    }
}

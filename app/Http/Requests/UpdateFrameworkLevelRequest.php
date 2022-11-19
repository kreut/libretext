<?php

namespace App\Http\Requests;

use App\Rules\IsValidUpdatedFrameworkLevel;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFrameworkLevelRequest extends FormRequest
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
           'title' =>['required', new IsValidUpdatedFrameworkLevel($this->framework_level_id)]
        ];
    }
}

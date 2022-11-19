<?php

namespace App\Http\Requests;

use App\Rules\IsValidNewFrameworkLevel;
use Illuminate\Foundation\Http\FormRequest;

class StoreFrameworkLevelRequest extends FormRequest
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
            'title'=>['required', new IsValidNewFrameworkLevel($this->framework_id,$this->level_to_add)]
        ];
    }
}

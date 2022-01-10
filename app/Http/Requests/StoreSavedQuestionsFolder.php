<?php

namespace App\Http\Requests;

use App\Rules\IsValidSavedQuestionsFolder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedQuestionsFolder extends FormRequest
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
            'name' => new IsValidSavedQuestionsFolder($this->type, $this->folder_id)
        ];
    }
}

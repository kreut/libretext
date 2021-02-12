<?php

namespace App\Http\Requests;

use App\Http\Controllers\LibreverseController;
use App\Libretext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLearningTreeInfo extends FormRequest
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
        $libretext = new Libretext();
        $rules = [
            'title' => 'required',
            'description' => 'required',
            'page_id' => 'required|integer|min:0',
            'library' => ['required', Rule::in($libretext->libraries())]
        ];
        return $rules;
    }
}


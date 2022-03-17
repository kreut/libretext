<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\Rules\IsValidCourseNameConfirmation;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\VaporCli\Helpers;

class ResetCourse extends FormRequest
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


        $rules['confirmation'] = ['required', new IsValidCourseNameConfirmation($this->course)];
        if (!Helper::isAdmin()) {
            $rules['understand_scores_removed'] = 'required|in:1';
        }
        return $rules;
    }

    public function messages()
    {
        $messages['understand_scores_removed.in'] = "You must confirm that you understand that all scores will be removed.";
        return $messages;
    }
}

<?php

namespace App\Http\Requests;

use App\Rules\CaseStudyNotes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseStudyNotes extends FormRequest
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
            'case_study_notes' => ['required', new CaseStudyNotes()]
        ];
    }
}

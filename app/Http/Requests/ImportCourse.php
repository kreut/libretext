<?php

namespace App\Http\Requests;

use App\Rules\IsValidAlphaCourseImportCode;
use Illuminate\Foundation\Http\FormRequest;

class ImportCourse extends FormRequest
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

        if ($this->import_as_beta) {
            if (!$this->alpha_course_import_code) {//in case they mess with the form
                $this->merge(['alpha_course_import_code' => '']);
            }
            return [
                'alpha_course_import_code' => ['required', new IsValidAlphaCourseImportCode()]
            ];
        } else {
            return [];//nothing to validate
        }
    }
}

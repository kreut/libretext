<?php

namespace App\Http\Requests;

use App\Rules\IsValidCourseForBulkUpload;
use Illuminate\Foundation\Http\FormRequest;

class H5PCollectionImport extends FormRequest
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
            'folder_id' => 'required|numeric|gt:0|not_in:0',
            'collection' => 'required'
        ];
        if ($this->import_to_course) {
            $rules['import_to_course'] = [new IsValidCourseForBulkUpload()];
            $rules['assignment_template'] = 'required';
        }
        return $rules;
    }

    public function messages() {
        $messages['assignment_template.required'] = "Please choose an assignment template.";
        $messages['folder_id'][0] = "Please choose a folder.";
        return $messages;

    }
}

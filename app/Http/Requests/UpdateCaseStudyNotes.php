<?php

namespace App\Http\Requests;

use App\Rules\CaseStudyNotes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => ['required',
                Rule::in(['history_and_physical',
                    'progress_notes',
                    'vital_signs',
                    'lab_results',
                    'provider_orders',
                    'mar',
                    'handoff_report'])]
        ];
    }
}

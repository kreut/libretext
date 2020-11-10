<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentGroup extends FormRequest
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
            'assignment_group' => ['required',
                Rule::unique('assignment_groups')->where(function ($query) {
                    return $query->where('course_id', $this->route('course')->id)->orWhere('course_id',0);
                })
        ]
            ];
    }
}

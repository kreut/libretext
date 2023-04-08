<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreRubricCategoryRequest extends FormRequest
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
//Log::info(print_r($this->route()->parameters('rubricCategory')[''],1));
        $unique = Rule::unique('rubric_categories')
            ->where('assignment_id', $this->assignment_id);
        if ($this->getMethod() === 'PATCH') {
            $unique = $unique->ignore($this->route()->parameters()['rubricCategory']->id);
        }
        $percent = str_replace('%', '', $this->input('percent'));
        $this->merge(['percent'=>$percent]);
        return [
            'category' => ['required', $unique],
            'criteria' => 'required',
            'percent' => ['required', 'numeric', 'between:0,100']
        ];
    }
}

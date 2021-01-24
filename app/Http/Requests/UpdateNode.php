<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNode extends FormRequest
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
        return  [
            'page_id' => 'required|integer|min:0',
            'library' => ['required', Rule::in(['bio','biz','chem','eng','espanol','geo','human','k12','law','math','med','phys','query','socialsci','stats','workforce'])]
        ];

    }
}

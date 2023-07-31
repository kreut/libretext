<?php

namespace App\Http\Requests;

use App\Libretext;
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

        $rules =  ['question_id' => 'required|integer|min:0'];
        if (!$this->is_root_node){
            $rules['node_description'] = 'required';
        }
        return $rules;
    }
}

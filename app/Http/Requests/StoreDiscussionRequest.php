<?php

namespace App\Http\Requests;

use Exception;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscussionRequest extends FormRequest
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
     * @throws Exception
     */
    public function rules(): array
    {
        switch ($this->type) {
            case('text'):
                $rules = ['text' => 'required'];
                break;
            case('file'):
                $rules = ['file' => 'required'];
                break;
            default:
                throw new Exception ("This type of comment has no validation associated with it.");
        }
        return $rules;
    }
}

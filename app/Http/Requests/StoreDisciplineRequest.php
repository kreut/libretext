<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisciplineRequest extends FormRequest
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
    public function rules(): array
    {
        $unique = Rule::unique('disciplines')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($this->name)]);
            });
        if ($this->route()->getActionMethod() === 'update') {
            $unique->ignore($this->route()->parameters()['discipline']->id);
        }
        $rules['name'] = ['required', $unique];
        return $rules;
    }
}


<?php

namespace App\Http\Requests;

use App\Rules\UniqueWebworkMacroName;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebworkMacroRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $webwork_macro = $this->route('webworkMacro');
        $ignore_id     = $webwork_macro ? $webwork_macro->id : null;

        return [
            'name'           => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+\.pl$/',
                new UniqueWebworkMacroName($ignore_id),
            ],
            'description'    => 'required|string',
            'macro'          => 'required|string',
            'reason_for_edit'=>  $ignore_id ? 'required|string|max:1000' : 'nullable'
        ];
    }

    public function messages()
    {
        return [
            'name.required'        => 'A macro name is required.',
            'name.regex'           => 'The macro name may only contain letters, numbers, underscores, and hyphens, and must end in .pl (e.g. randomLinearEquation.pl).',
            'description.required' => 'A description is required.',
            'macro.required'       => 'The macro body is required.',
        ];
    }
}

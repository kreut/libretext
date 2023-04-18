<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateGradingStyleRequest extends FormRequest
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
        $grading_styles = DB::table('grading_styles')
            ->select('id')
            ->get()
            ->pluck('id')
            ->toArray();
        return [
            'grading_style_id' => ['required',Rule::in($grading_styles)]
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionSectionRequest extends FormRequest
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

        if ($this->isMethod('PATCH')) {
            $question_chapter_id = $this->question_chapter_id;
        } else {
            $question_chapter_id = $this->question_chapter->id;
        }


        $unique = Rule::unique('question_sections', 'name')
            ->where(function ($query) use ($question_chapter_id) {
                $query->where('question_chapter_id', $question_chapter_id);
            });
        if ($this->isMethod('PATCH')) {
            $unique = $unique->ignore($this->route('question_section'));
        }
        return [
            'name' => ['required', $unique]
        ];
    }
}

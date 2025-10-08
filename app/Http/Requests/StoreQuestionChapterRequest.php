<?php

namespace App\Http\Requests;

use App\QuestionChapter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionChapterRequest extends FormRequest
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
            $question_subject_id = $this->question_subject_id;
        } else {
            $question_subject_id = $this->question_subject->id;
        }


        $unique = Rule::unique('question_chapters', 'name')
            ->where(function ($query) use ($question_subject_id) {
                $query->where('question_subject_id', $question_subject_id);
            });
        if ($this->isMethod('PATCH')) {
            $unique = $unique->ignore($this->route('question_chapter'));
        }

        return [
            'name' => ['required', $unique]
        ];
    }
}

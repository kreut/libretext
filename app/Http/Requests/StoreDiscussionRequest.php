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
        $rules['timestamps'] = 'nullable|array';
        $rules['timestamps.*.discussion_comment_id'] = 'required|integer';
        $rules['timestamps.*.seconds'] = 'required|numeric|min:0';
        //files attached to the comment; their type and size are checked when the comment is saved
        $rules['attachments'] = 'nullable|array|max:' . \App\DiscussionCommentAttachment::MAX_PER_COMMENT;
        $rules['attachments.*.file'] = 'required|string|max:191';
        $rules['attachments.*.original_filename'] = 'required|string|max:255';
        return $rules;
    }
}

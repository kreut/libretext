<?php

namespace App\Http\Requests;

use App\AutoRelease;
use App\Rules\IsValidPeriodOfTime;
use Illuminate\Foundation\Http\FormRequest;

class AutoReleaseRequest extends FormRequest
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
        $rules = [];
        $auto_releases = ['auto_release_shown', 'auto_release_show_scores', 'auto_release_solutions_released', 'auto_release_students_can_view_assignment_statistics'];

        foreach ($auto_releases as $auto_release) {
            if ($this->{$auto_release}) {
                $rules[$auto_release] = new IsValidPeriodOfTime();
                if ($auto_release !== 'auto_release_shown') {
                    $rules[$auto_release . "_after"] = 'required';
                }
            }
            if ($auto_release !== 'auto_release_shown' && $this->{$auto_release . "_after"}) {
                $rules[$auto_release] = 'required';
            }
        }
        return $rules;
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        $autoRelease = new AutoRelease();
        return $autoRelease->requestMessages();

    }
}

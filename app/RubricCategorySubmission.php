<?php

namespace App;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RubricCategorySubmission extends Model
{
    protected $guarded = [];

    /**
     * @param RubricCategory $rubricCategory
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param int $assignment_id
     * @param string $submission
     * @return void
     */
    public function initProcessing(RubricCategory           $rubricCategory,
                                   RubricCategorySubmission $rubricCategorySubmission,
                                   int                      $assignment_id,
                                   string                   $submission)
    {
        $rubricCategory = $rubricCategory->find($rubricCategory->id);
        $question = Question::find($rubricCategory->question_id);
        $grading_style = DB::table('grading_styles')->where('id', $question->grading_style_id)->first();
        $grading_style_description = $grading_style ? $grading_style->description : '';

        $post_fields = ['user_id' => $rubricCategorySubmission->user_id,
            'rubric_category_id' => $rubricCategory->id,
            'batch_id' => $assignment_id,
            'submission' => $submission,
            'points' => $rubricCategory->percent,
            'purpose' => $question->purpose,
            'criteria' => $rubricCategory->criteria,
            'category' => $rubricCategory->category,
            'grading_style_description' => $grading_style_description,
            'type' => 'lab report',
            'subject' => 'chemistry',
            'level' => 'college',
            'results_url' => request()->getSchemeAndHttpHost() . '/api/open-ai/results/lab-report',
            'iss' => request()->getSchemeAndHttpHost()];
        $curl = curl_init();
        switch (app()->environment()) {
            case('local'):
                $url = 'https://feedback:8890';
                break;
            case('staging'):
            case('dev'):
                $url = 'https://myessayeditor-staging.com';
                break;
            default:
                $url = 'https://feedback.ai';
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . '/api/external',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . config('myconfig.my_essay_feedback_token')
            ),
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_SSL_VERIFYPEER => app()->environment() !== 'local'
        ));

        $curl_response = curl_exec($curl);
        if (curl_errno($curl)) {
            $rubricCategorySubmission->status = 'error';
            $rubricCategorySubmission->message = curl_error($curl);
        } else {
            $response_arr = json_decode($curl_response, 1);

            $rubricCategorySubmission->status = $response_arr['type'];
            $rubricCategorySubmission->message = $response_arr['message'];

        }
        $rubricCategorySubmission->save();
    }

    /**
     * @param $assignment
     * @return array
     */
    public function getRubricCategorySubmissionsByUser($assignment): array
    {

        $rubric_category_submissions = DB::table('rubric_category_submissions')
            ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
            ->where('assignment_id', $assignment->id)
            ->select()
            ->orderBy('order')
            ->get();

        $rubric_category_submissions_by_user = [];


        foreach ($rubric_category_submissions as $submission) {
            if (!isset($rubric_category_submissions_by_user[$submission->user_id])) {
                $rubric_category_submissions_by_user[$submission->user_id] = [];
            }
            $rubric_category_submissions_by_user[$submission->user_id][] = $submission;
        }
        return $rubric_category_submissions_by_user;
    }
}

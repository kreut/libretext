<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Question;
use App\RubricCategory;
use App\RubricCategoryCustomCriteria;
use App\RubricCategorySubmission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RubricCategoryCustomCriteriaController extends Controller
{
    /**
     * @param Request $request
     * @param RubricCategoryCustomCriteria $rubricCategoryCustomCriteria
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param RubricCategory $rubricCategory
     * @return array
     * @throws Exception
     */
    public function store(Request                      $request,
                          RubricCategoryCustomCriteria $rubricCategoryCustomCriteria,
                          RubricCategorySubmission     $rubricCategorySubmission,
                          RubricCategory               $rubricCategory): array
    {
        $response['type'] = 'error';
        try {
            $rubric_custom_criteria = $rubricCategoryCustomCriteria
                ->where('assignment_id', $request->assignment_id)
                ->where('rubric_category_id', $request->rubric_category_id)
                ->first();
            if (!$rubric_custom_criteria) {
                $rubric_custom_criteria = new RubricCategoryCustomCriteria();
                $rubric_custom_criteria->assignment_id = $request->assignment_id;
                $rubric_custom_criteria->rubric_category_id = $request->rubric_category_id;
            }
            $rubric_custom_criteria->custom_criteria = $request->custom_criteria;
            $rubric_custom_criteria->save();


            $rubricCategory = $rubricCategory->find($request->rubric_category_id);
            $question = Question::find($rubricCategory->question_id);
            $grading_style = DB::table('grading_styles')->where('id', $question->grading_style_id)->first();
            $grading_style_description = $grading_style ? $grading_style->description : '';
            $rubricCategorySubmissions = $rubricCategorySubmission->where('assignment_id', $request->assignment_id)
                ->where('rubric_category_id', $request->rubric_category_id)
                ->get();
            DB::beginTransaction();
            foreach ($rubricCategorySubmissions as $rubricCategorySubmission) {
                $rubricCategorySubmission->status = 'pending';
                $rubricCategorySubmission->message = null;
                $rubricCategorySubmission->save();
                DB::table('rubric_category_criteria_pendings')->updateOrInsert(
                    [
                        'assignment_id' => $rubricCategorySubmission->assignment_id,
                        'rubric_category_id' => $rubricCategorySubmission->rubric_category_id,
                        'user_id' => $rubricCategorySubmission->user_id,
                        'notify_user_id'=> $request->user()->id],
                    ['processed' => 0, 'updated_at' => now(), 'created_at' => now()]);
            }

            foreach ($rubricCategorySubmissions as $rubricCategorySubmission) {
                $rubricCategorySubmission->postToAI($rubricCategorySubmission,
                    $rubricCategorySubmission->user_id,
                    $rubricCategory->id,
                    $request->assignment_id,
                    $rubricCategorySubmission->submission,
                    $rubricCategory->score,
                    $question->purpose,
                    $request->custom_criteria,
                    $rubricCategory->category,
                    $grading_style_description,
                    '/api/open-ai/results/lab-report');
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The AI is now re-processing the submissions with the new criteria.  When fully completed, you will be notified by email.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error applying the custom criteria. Please try again.";

        }
        return $response;

    }
}

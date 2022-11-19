<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\FrameworkDescriptor;
use App\FrameworkItemSyncQuestion;
use App\Question;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;


class FrameworkItemSyncQuestionController extends Controller
{
    /**
     * @param FrameworkDescriptor $frameworkDescriptor
     * @param FrameworkItemSyncQuestion $frameworkItemSyncQuestion
     * @return array
     * @throws Exception
     */
    public function getQuestionsByDescriptor(FrameworkDescriptor $frameworkDescriptor,
                                             FrameworkItemSyncQuestion $frameworkItemSyncQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getQuestionsByDescriptor', $frameworkItemSyncQuestion);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $questions_synced_to_descriptor = DB::table('framework_item_question')
                ->join('questions', 'framework_item_question.question_id', '=', 'questions.id')
                ->where('framework_item_type', 'descriptor')
                ->where('framework_item_id', $frameworkDescriptor->id)
                ->get();
            $response['questions_synced_to_descriptor'] = $questions_synced_to_descriptor;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error get the descriptors for this question.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Question $question
     * @param FrameworkItemSyncQuestion $frameworkItemSyncQuestion
     * @return array
     * @throws Exception
     */
    public function getFrameworkItemsByQuestion(Question                  $question,
                                                FrameworkItemSyncQuestion $frameworkItemSyncQuestion): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getFrameworkItemsByQuestion', $frameworkItemSyncQuestion);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $descriptors = DB::table('framework_item_question')
                ->join('framework_descriptors', 'framework_item_question.framework_item_id', '=', 'framework_descriptors.id')
                ->where('framework_item_type', 'descriptor')
                ->where('question_id', $question->id)
                ->select('framework_descriptors.id', 'framework_descriptors.descriptor AS text')
                ->get();
            $descriptors_by_id = [];
            foreach ($descriptors as $descriptor) {
                $descriptors_by_id[] = ['id' => $descriptor->id,
                    'text' => $descriptor->text];
            }

            $framework_levels = DB::table('framework_item_question')
                ->join('framework_levels', 'framework_item_question.framework_item_id', '=', 'framework_levels.id')
                ->where('framework_item_type', 'level')
                ->where('question_id', $question->id)
                ->select('framework_levels.id', 'framework_levels.title AS text')
                ->get();
            $framework_levels_by_id = [];
            foreach ($framework_levels as $framework_level) {
                $framework_levels_by_id[] = ['id' => $framework_level->id,
                    'text' => $framework_level->text];
            }
            $response['type'] = 'success';
            $response['framework_item_sync_question'] = [
                'descriptors' => $descriptors_by_id,
                'levels' => $framework_levels_by_id
            ];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error get the framework alignments for this question.  Please try again or contact us for assistance.";
        }
        return $response;

    }

}

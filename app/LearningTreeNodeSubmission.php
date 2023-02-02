<?php

namespace App;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSubmission;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class LearningTreeNodeSubmission extends Model
{


    protected $guarded = [];


    public function store(StoreSubmission                $request,
                          AssignmentQuestionLearningTree $assignmentQuestionLearningTree,
                          LearningTree                   $learningTree,
                          DataShop                       $dataShop): array
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message
        $data = $request;

//need learning tree id, should get branch question id
        $data['user_id'] = Auth::user()->id;

        $assignment = Assignment::find($request->assignment_id);

        //verify it's one of the nodes

        /* $assignment = $Assignment->find($data['assignment_id']);
       $authorized = Gate::inspect('store', [$remediationSubmission, $assignment, $assignment->id, $data['question_id']]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/

        try {
            $data = $request;
            $Submission = new Submission();
            switch ($data['technology']) {
                case('h5p'):
                    $submission = json_decode($data['submission']);
                    //hotspots don't have anything
                    $no_submission = isset($submission->result->response) && str_replace('[,]', '', $submission->result->response) === '';
                    if ($no_submission) {
                        $response['type'] = 'info';
                        $response['message'] = $response['not_updated_message'] = "It looks like you've submitted a blank response.  Please make a selection before submitting.";
                        return $response;
                    }
                    $proportion_correct = $Submission->getProportionCorrect('h5p', $submission);
                    break;
                case('imathas'):
                    $submission = $data['submission'];
                    $proportion_correct = $Submission->getProportionCorrect('imathas', (object)$submission);
                    $data['submission'] = json_encode($data['submission']);
                    break;
                case('webwork'):
                    $submission = $data['submission'];
                    $proportion_correct = $Submission->getProportionCorrect('webwork', (object)$submission);//
                    $data['submission'] = json_encode($data['submission']);
                    break;
                case('qti'):
                    $question = DB::table('questions')
                        ->where('id', $data['question_id'])->first();
                    if (!$question) {
                        $response['message'] = "{$data['question_id']} does not exist in the database.";
                        return $response;
                    }
                    $submission_arr = ['question' => json_decode($question->qti_json), 'student_response' => $data['submission']];

                    $submission = json_decode(json_encode($submission_arr));
                    $proportion_correct = $Submission->getProportionCorrect('qti', $submission);
                    $submission->proportion_correct = $proportion_correct;
                    $data['submission'] = json_encode($submission);
                    break;
                default:
                    $response['message'] = 'That is not a valid technology.';
                    return $response;
            }
            $correct_submission = 1 - $proportion_correct < PHP_FLOAT_EPSILON;


            $Assignment = Assignment::find($data['assignment_id']);
            $past_due = time() > strtotime($Assignment->assignToTimingByUser('due'));


            /*   if ($past_due) {
                   $response['message'] = $correct_submission ? "Your submission was correct.  However, this assignment is closed and will not count towards a Root Assessment reset." : "Your submission was not correct. You may try again, but since this assignment is closed, it won't count towards a Root Assessment reset.";
                   $response['type'] = 'success';
                   return $response;
               }*/


            //do the extension stuff also
            DB::beginTransaction();
            $previously_completed_final_question_ids = LearningTreeNodeSubmission::where('user_id', $data['user_id'])
                ->where('learning_tree_id', $learningTree->id)
                ->where('assignment_id', $assignment->id)
                ->whereIn('question_id', $learningTree->finalQuestionIds())
                ->where('proportion_correct', 1)
                ->get()
                ->pluck('question_id')
                ->toArray();


            $learningTreeNodeSubmission = LearningTreeNodeSubmission::where('user_id', $data['user_id'])
                ->where('assignment_id', $assignment->id)
                ->where('learning_tree_id', $learningTree->id)
                ->where('question_id', $data['question_id'])
                ->first();
            $completed = (float)$proportion_correct === 1.0;
            if ($learningTreeNodeSubmission) {
                $learningTreeNodeSubmission->submission = $data['submission'];
                $learningTreeNodeSubmission->proportion_correct = $proportion_correct;
                $learningTreeNodeSubmission->completed = $completed;
                $learningTreeNodeSubmission->submission_count = $learningTreeNodeSubmission->submission_count + 1;
                $learningTreeNodeSubmission->save();

            } else {
                $learningTreeNodeSubmission = LearningTreeNodeSubmission::create([
                    'user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'learning_tree_id' => $data['learning_tree_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'proportion_correct' => $proportion_correct,
                    'completed' => $completed,
                    'submission_count' => 1]);
            }
            $learningTreeNodeSubmission->check_for_reset = $correct_submission
                && in_array($data['question_id'], $learningTree->finalQuestionIds())
                && !in_array($data['question_id'], $previously_completed_final_question_ids);
            $learningTreeNodeSubmission->show_submission_message = 1;
            $learningTreeNodeSubmission->save();

            /*
                        $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByLearningTreeId($data['assignment_id'], $data['learning_tree_id']);
                        $learningTreeNodeSubmission->check_for_reset = $correct_submission
                            && in_array($data['question_id'], $learningTree->finalQuestionIds())
                            && !in_array($data['question_id'], $previously_completed_final_question_ids);
                        $learningTreeNodeSubmission->save();
                        $earned_reset = false;
                        if ($correct_submission
                            && in_array($data['question_id'], $learningTree->finalQuestionIds())
                            && !in_array($data['question_id'], $previously_completed_final_question_ids)) {
                            $learningTreeReset = LearningTreeReset::where('user_id', $data['user_id'])
                                ->where('assignment_id', $assignment->id)
                                ->where('learning_tree_id', $learningTree->id)
                                ->first();
                            if (!$learningTreeReset) {
                                $learningTreeReset = new LearningTreeReset();
                                $learningTreeReset->user_id = $data['user_id'];
                                $learningTreeReset->assignment_id = $assignment->id;
                                $learningTreeReset->learning_tree_id = $learningTree->id;
                                $learningTreeReset->number_resets_available = 0;
                                $learningTreeReset->save();
                            }
                            $number_paths_completed = count($previously_completed_final_question_ids) + 1;
                            $earned_reset = $number_paths_completed % $assignment_question_learning_tree->number_of_successful_paths_for_a_reset === 0;
                            $number_resets_available = $earned_reset ? $learningTreeReset->number_resets_available + 1 : $learningTreeReset->number_resets_available;

                            $learningTreeReset->number_resets_available = $number_resets_available;
                            $learningTreeReset->save();
                        }
                        $message = $correct_submission ? "Your submission was correct. " : "Your submission was not correct.  ";
                        $message .= $earned_reset ? "You have earned a reset and can retry the original question." : '';
            */

            $response['type'] = 'success';
            $response['learning_tree_node_submission_id'] = $learningTreeNodeSubmission->id;//needed for client side technologies
            /*
            $response['message'] = $message;
            $response['earned_reset'] = $earned_reset;
            $response['correct_submission'] = $correct_submission;
            $response['completed'] = $completed;
            $response['learning_tree_node_submission_id'] = $learningTreeNodeSubmission->id;*/


            DB::commit();
            //don't really care if this gets messed up from the user perspective
            /*try {
               // session()->put('submission_id', md5(uniqid('', true)));
              //  $dataShop->store($learningTreeNodeSubmission, $data, $assignment, $assignment_question);
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);

            }*/


        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }

        return $response;

    }
}

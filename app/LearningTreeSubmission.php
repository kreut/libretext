<?php

namespace App;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSubmission;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningTreeSubmission extends Model
{
    /**
     * @throws Exception
     */
    public function store(StoreSubmission        $request,
                          Assignment             $Assignment,
                          DataShop               $dataShop): array
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message
        $data = $request;
dd($request->all());
//need learning tree id, should get branch question id
      aaa

        $data['user_id'] = Auth::user()->id;
        $assignment = $Assignment->find($data['assignment_id']);

        //verify it's one of the remediation nodes


        /*$authorized = Gate::inspect('store', [$learningTreeSubmission, $assignment, $assignment->id, $data['question_id']]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/

        try {
            $data = $request;
            $submission = new Submission();
            switch ($data['technology']) {
                case('h5p'):
                    $learningTreeSubmission = json_decode($data['submission']);
                    //hotspots don't have anything
                    $no_submission = isset($learningTreeSubmission->result->response) && str_replace('[,]', '', $learningTreeSubmission->result->response) === '';
                    if ($no_submission) {
                        $response['type'] = 'info';
                        $response['message'] = $response['not_updated_message'] = "It looks like you've submitted a blank response.  Please make a selection before submitting.";
                        return $response;
                    }
                    $proportion_correct = $submission->getProportionCorrect('h5p', $learningTreeSubmission);
                    break;
                case('imathas'):
                    $learningTreeSubmission = $data['submission'];
                    $proportion_correct = $submission->getProportionCorrect('imathas', $learningTreeSubmission);
                    break;
                case('webwork'):
                    $learningTreeSubmission = $data['submission'];
                    $proportion_correct = $submission->getProportionCorrect('webwork', (object)$learningTreeSubmission);//
                    $data['submission'] = json_encode($data['submission']);
                    break;
                default:
                    $response['message'] = 'That is not a valid technology.';
                    return $response;
            }


            //do the extension stuff also
            if ($learningTreeSubmission) {

                $learningTreeSubmission->submission = $data['submission'];
                $learningTreeSubmission->proportion_correct = $proportion_correct;
                $learningTreeSubmission->submission_count = $learningTreeSubmission->submission_count + 1;
                $learningTreeSubmission->save();

            } else {


                LearningTreeSubmission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'answered_correctly_at_least_once' => $data['all_correct'],
                    'submission_count' => 1]);
            }
            //update the score if it's supposed to be updated

            $response['type'] = 'success';
            $response['message'] = "Your submission was saved.";
            $response['explored_learning_tree'] = "to do";
            $response['learning_tree_message'] = "to do";

            //don't really care if this gets messed up from the user perspective
            /*try {
               // session()->put('submission_id', md5(uniqid('', true)));
              //  $dataShop->store($learningTreeSubmission, $data, $assignment, $assignment_question);
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);

            }*/


        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }

        return $response;

    }
}

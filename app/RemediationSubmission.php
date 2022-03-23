<?php

namespace App;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSubmission;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RemediationSubmission extends Model
{
    protected $guarded = [];

    /**
     * @throws Exception
     */
    public function store(StoreSubmission $request,
                          Assignment      $Assignment,
                          DataShop        $dataShop): array
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message
        $data = $request;

//need learning tree id, should get branch question id
        $data['user_id'] = Auth::user()->id;


        //verify it's one of the remediation nodes

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
                    $proportion_correct = $Submission->getProportionCorrect('imathas', $submission);
                    break;
                case('webwork'):
                    $submission = $data['submission'];
                    $proportion_correct = $Submission->getProportionCorrect('webwork', (object)$submission);//
                    $data['submission'] = json_encode($data['submission']);
                    break;
                default:
                    $response['message'] = 'That is not a valid technology.';
                    return $response;
            }


            //do the extension stuff also

            $remediationSubmission = RemediationSubmission::where('user_id', $request->user()->id)
                ->where('assignment_id', $data['assignment_id'])
                ->where('learning_tree_id', $data['learning_tree_id'])
                ->where('question_id', $data['question_id'])
                ->first();
            if ($remediationSubmission) {
                $remediationSubmission->submission = $data['submission'];
                $remediationSubmission->proportion_correct = $proportion_correct;
                $remediationSubmission->submission_count = $remediationSubmission->submission_count + 1;
                $remediationSubmission->save();

            } else {
                RemediationSubmission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'learning_tree_id' => $data['learning_tree_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'proportion_correct' => $proportion_correct,
                    'submission_count' => 1]);
            }
            //update the score if it's supposed to be updated

            $response['type'] = 'success';
            $response['message'] = "Your submission was saved.";
            $response['can_resubmit_root_node_question'] = $this->canResubmitRootNodeQuestion($data['user_id'], $data['assignment_id'], $data['learning_tree_id']);


            $response['explored_learning_tree'] = "to do";
            $response['learning_tree_message'] = "to do";

            //don't really care if this gets messed up from the user perspective
            /*try {
               // session()->put('submission_id', md5(uniqid('', true)));
              //  $dataShop->store($remediationSubmission, $data, $assignment, $assignment_question);
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

    public function canResubmitRootNodeQuestion(int $user_id, int $assignment_id, int $learning_tree_id)
    {
        $assignment = Assignment::find($assignment_id);
        $learningTree = LearningTree::find($learning_tree_id);
        $can_resubmit_root_node_question = false;

        $learning_tree_branch_structure = $learningTree->getBranchStructure();
        $branch_and_twig_info = $learningTree->getBranchAndTwigInfo($learning_tree_branch_structure);
        $branch_and_twigs_with_success_with_success_info = $this->getBranchAndTwigWithSuccessInfo($branch_and_twig_info, $user_id, $assignment_id, $learning_tree_id);
      dd(        $branch_and_twigs_with_success_with_success_info );
      $success_criteria_satisfied = $this->successCriteriaSatisfied($assignment, $branch_and_twigs_with_success_with_success_info);

        return $can_resubmit_root_node_question;
    }

    public function successCriteriaSatisfied(Assignment $assignment, array $branch_and_twigs_with_success_with_success_info): bool
    {
        $success_criteria_satistfied = false;
        switch ($assignment->learning_tree_success_level) {
            case('tree'):
                if ($assignment->learning_tree_success_criteria === 'time based') {


                }
                break;

            case('branch'):


                break;
        }
        return $success_criteria_satistfied;
    }

    /**
     * @param array $branch_and_twig_info
     * @param int $user_id
     * @param int $assignment_id
     * @param int $learning_tree_id
     * @return array
     */
    public function getBranchAndTwigWithSuccessInfo(array $branch_and_twig_info, int $user_id, int $assignment_id, int $learning_tree_id): array
    {
        $remediation_submissions = DB::table('remediation_submissions')
            ->where('user_id', $user_id)
            ->where('assignment_id', $assignment_id)
            ->where('learning_tree_id', $learning_tree_id)
            ->select('time_spent', 'proportion_correct', 'question_id')
            ->get();
        foreach ($remediation_submissions as $remediation_submission) {
            $remediation_submissions_by_question_id[$remediation_submission->question_id] = [
                'time_spent' => $remediation_submission->time_spent,
                'proportion_correct' => $remediation_submission->proportion_correct];
        }
        foreach ($branch_and_twig_info as $info) {
            foreach ($info['twigs'] as $key => $twig) {
                $info['twigs'][$key]['question_info']->time_spent = $remediation_submissions_by_question_id[$twig['question_info']->id]['time_spent'] ?? 0;
                $info['twigs'][$key]['question_info']->proportion_correct = $remediation_submissions_by_question_id[$twig['question_info']->id]['proportion_correct'] ?? 0;
            }
        }
        return $branch_and_twig_info;
    }

}

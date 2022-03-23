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
            DB::beginTransaction();
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
            $assignment_question_learning_tree = $this->getAssignmentQuestionLearningTree($data['assignment_id'], $data['learning_tree_id']);
            //update the score if it's supposed to be updated
            $learning_tree_success_criteria_satisfied = $this->canResubmitRootNodeQuestion($data['user_id'], $data['assignment_id'], $data['learning_tree_id']);
            if ($learning_tree_success_criteria_satisfied) {
                DB::table('submissions')
                    ->where('user_id', $data['user_id'])
                    ->where('assignment_id', $data['assignment_id'])
                    ->where('question_id', $assignment_question_learning_tree->question_id)
                    ->update(['learning_tree_success_criteria_satisfied' => 1]);
            }

            $response['type'] = 'success';
            $response['message'] = "Your submission was saved.";
            $response['learning_tree_success_criteria_satisfied'] = $learning_tree_success_criteria_satisfied;

            $response['explored_learning_tree'] = "to do";
            $response['learning_tree_message'] = "to do";


            DB::commit();
            //don't really care if this gets messed up from the user perspective
            /*try {
               // session()->put('submission_id', md5(uniqid('', true)));
              //  $dataShop->store($remediationSubmission, $data, $assignment, $assignment_question);
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

    /**
     * @throws Exception
     */
    public function canResubmitRootNodeQuestion(int $user_id, int $assignment_id, int $learning_tree_id): bool
    {
        $assignment = Assignment::find($assignment_id);
        $learningTree = LearningTree::find($learning_tree_id);
        $learning_tree_branch_structure = $learningTree->getBranchStructure();
        $branch_and_twig_info = $learningTree->getBranchAndTwigInfo($learning_tree_branch_structure);
        $branch_and_twigs_with_success_with_success_info = $this->getBranchAndTwigWithSuccessInfo($branch_and_twig_info, $assignment, $user_id, $learning_tree_id);;
        return $this->successCriteriaSatisfied($branch_and_twigs_with_success_with_success_info, $assignment_id, $learning_tree_id);
    }

    /**
     * @throws Exception
     */
    public function successCriteriaSatisfied(array $branch_and_twigs_with_success_with_success_info,
                                             int   $assignment_id,
                                             int   $learning_tree_id): bool
    {

        $assignment_question_learning_tree = $this->getAssignmentQuestionLearningTree($assignment_id, $learning_tree_id);

        switch ($assignment_question_learning_tree->learning_tree_success_level) {
            case('tree'):
                $success_criteria_satisfied = $this->successCriteriaSatisfiedAtTheTreeLevel($assignment_question_learning_tree, $branch_and_twigs_with_success_with_success_info);
                break;

            case('branch'):
                $success_criteria_satisfied = $this->successCriteriaSatisfiedAtTheBranchLevel($assignment_question_learning_tree, $branch_and_twigs_with_success_with_success_info);
                break;
            default:
                throw new Exception("$assignment_question_learning_tree->learning_tree_success_level is not a valid learning tree success level.");
        }
        return $success_criteria_satisfied;
    }

    /**
     * @throws Exception
     */
    public function getAssignmentQuestionLearningTree(int $assignment_id, int $learning_tree_id)
    {
        $assignment_question_learning_tree = DB::table('assignment_question')
            ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
            ->where('assignment_question.assignment_id', $assignment_id)
            ->where('assignment_question_learning_tree.learning_tree_id', $learning_tree_id)
            ->first();
        if (!$assignment_question_learning_tree) {
            throw new Exception ("Assignment question with assignment id $assignment_id and learning tree id $learning_tree_id does not exist.");
        }
        return $assignment_question_learning_tree;
    }

    /**
     * @throws Exception
     */
    public function successCriteriaSatisfiedAtTheBranchLevel($assignment_question_learning_tree, array $branch_and_twigs_with_success_with_success_info): bool
    {
        $num_successful_branches = 0;
        foreach ($branch_and_twigs_with_success_with_success_info as $info) {
            $branch_success = false;
            switch ($assignment_question_learning_tree->learning_tree_success_criteria) {
                case('time based'):
                    $time_spent_in_branch = 0;
                    foreach ($info['twigs'] as $twig) {
                        $time_spent_in_branch += $twig['question_info']->time_spent;
                        if ($time_spent_in_branch >= $assignment_question_learning_tree->min_time) {
                            $branch_success = true;
                        }
                    }
                    break;
                case('assessment based'):
                    $num_successful_assessments_in_branch = 0;
                    foreach ($info['twigs'] as $twig) {
                        $num_successful_assessments_in_branch += abs($twig['question_info']->proportion_correct - 1) < PHP_FLOAT_EPSILON;
                        if ($num_successful_assessments_in_branch >= $assignment_question_learning_tree->min_number_of_successful_assessments) {
                            $branch_success = true;
                        }
                    }
                    break;
                default:
                    throw new Exception("$assignment_question_learning_tree->learning_tree_success_criteria is not a valid success criteria.");
            }
            if ($branch_success) {
                $num_successful_branches++;
            }
            if ($num_successful_branches >= $assignment_question_learning_tree->min_number_of_successful_branches) {
                return true;
            }
        }
        return false;
    }


    /**
     * @throws Exception
     */
    public function successCriteriaSatisfiedAtTheTreeLevel($assignment_question_learning_tree, array $branch_and_twigs_with_success_with_success_info): bool
    {
        switch ($assignment_question_learning_tree->learning_tree_success_criteria) {
            case('time based'):
                $total_time_spent = 0;
                foreach ($branch_and_twigs_with_success_with_success_info as $info) {
                    foreach ($info['twigs'] as $twig) {
                        $total_time_spent += $twig['question_info']->time_spent;
                        if ($total_time_spent >= $assignment_question_learning_tree->min_time) {
                            return true;
                        }
                    }
                }
                break;
            case('assessment based'):
                $total_correct_assessments = 0;
                foreach ($branch_and_twigs_with_success_with_success_info as $info) {
                    foreach ($info['twigs'] as $twig) {
                        $total_correct_assessments += abs($twig['question_info']->proportion_correct - 1) < PHP_FLOAT_EPSILON;
                        if ($total_correct_assessments >= $assignment_question_learning_tree->min_number_of_successful_assessments) {
                            return true;
                        }
                    }
                }
                break;
            default:
                throw new Exception("$assignment_question_learning_tree->learning_tree_success_criteria is not a valid success criteria.");
        }
        return false;
    }


    /**
     * @param array $branch_and_twig_info
     * @param Assignment $assignment
     * @param int $user_id
     * @param int $learning_tree_id
     * @return array
     */
    public function getBranchAndTwigWithSuccessInfo(array $branch_and_twig_info, Assignment $assignment, int $user_id, int $learning_tree_id): array
    {
        $remediation_submissions = DB::table('remediation_submissions')
            ->where('user_id', $user_id)
            ->where('assignment_id', $assignment->id)
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

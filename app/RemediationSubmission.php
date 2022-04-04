<?php

namespace App;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSubmission;
use App\Traits\LearningTreeSuccessCriteria;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RemediationSubmission extends Model
{
    protected $guarded = [];
    use LearningTreeSuccessCriteria;

    /**
     * @throws Exception
     */
    public function store(StoreSubmission                $request,
                          AssignmentQuestionLearningTree $assignmentQuestionLearningTree,
                          DataShop                       $dataShop): array
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
            $add_reset = false;
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
            $remediationSubmission = RemediationSubmission::where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('learning_tree_id', $data['learning_tree_id'])
                ->where('question_id', $data['question_id'])
                ->first();
            if ($remediationSubmission) {
                if ($this->correctBeforeButIncorrectNow($Submission, $data['technology'], $remediationSubmission->submission, $proportion_correct)) {

                    $response['type'] = 'success';
                    $response['message'] = "You previously answered this question correctly but answered it incorrectly now.  This will have no effect on the number of resets.";
                    $response['correct_submission'] = true;
                    $response['traffic_light_color'] = 'yellow';
                    $response['learning_tree_message'] = true;
                    $response['add_reset'] = false;
                    return $response;
                }

                $remediationSubmission->submission = $data['submission'];
                $remediationSubmission->proportion_correct = $proportion_correct;
                $remediationSubmission->submission_count = $remediationSubmission->submission_count + 1;
                $remediationSubmission->save();

            } else {
                RemediationSubmission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'learning_tree_id' => $data['learning_tree_id'],
                    'branch_id' => $data['branch_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'proportion_correct' => $proportion_correct,
                    'submission_count' => 1]);
            }

            $remediation_submissions_by_branch = DB::table('remediation_submissions')
                ->where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('learning_tree_id', $data['learning_tree_id'])
                ->where('branch_id', $data['branch_id'])
                ->select('proportion_correct')
                ->get();
            $num_correct_by_branch = 0;
            foreach ($remediation_submissions_by_branch as $remediation_submission) {
                if (1 - $remediation_submission->proportion_correct < PHP_FLOAT_EPSILON) {
                    $num_correct_by_branch++;
                }
            }

            $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByLearningTreeId($data['assignment_id'], $data['learning_tree_id']);
            $successful_branch = $num_correct_by_branch >= $assignment_question_learning_tree->min_number_of_successful_assessments;
            $successful_branch_exists = false;
            $root_node_question_id = DB::table('assignment_question')
                ->where('id', $assignment_question_learning_tree->assignment_question_id)
                ->select('question_id')
                ->first()
                ->question_id;
            $number_successful_branches_needed_for_a_reset = 0;
            if ($assignment_question_learning_tree->learning_tree_success_level === 'branch') {
                $successful_branch_info = $this->getSuccessfulBranchInfo($successful_branch,
                    $data['user_id'],
                    $data['assignment_id'],
                    $data['learning_tree_id'],
                    $data['branch_id'],
                    $root_node_question_id,
                    $assignment_question_learning_tree);
                $can_resubmit_root_node_question['success'] = $successful_branch_info['success'];
                $can_resubmit_root_node_question['message'] = $successful_branch_info['message'];
                $add_reset = $successful_branch_info['add_reset'];
                $traffic_light_color = $successful_branch_info['traffic_light_color'];
                $number_successful_branches_needed_for_a_reset = $successful_branch_info['number_successful_branches_needed_for_a_reset'];
                $successful_branch_exists = $successful_branch_info['successful_branch_exists'];
            } else {
                $can_resubmit_root_node_question = $this->canResubmitRootNodeQuestion($assignment_question_learning_tree, $data['user_id'], $data['assignment_id'], $data['learning_tree_id']);
                $traffic_light_color = $can_resubmit_root_node_question['success']
                    ? 'green'
                    : 'yellow';
                if ($can_resubmit_root_node_question['success']) {
                    $add_reset = true;
                }
            }

            $learning_tree_success_criteria_satisfied = $can_resubmit_root_node_question['success'];
            $this->updateReset($assignment_question_learning_tree->learning_tree_success_level,
                $learning_tree_success_criteria_satisfied,
                $successful_branch_exists,
                $number_successful_branches_needed_for_a_reset,
                $data['user_id'],
                $data['assignment_id'],
                $root_node_question_id);

            $correct_submission = 1 - $proportion_correct < PHP_FLOAT_EPSILON;
            $message = $correct_submission ? "Your submission was correct. " : "Your submission was not correct.  ";
            $submission = $Submission
                ->where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('question_id', $root_node_question_id)
                ->first();
            $assignment = Assignment::find($data['assignment_id']);
            $too_many_submissions = $submission->tooManySubmissions($assignment, $submission);
            if ($learning_tree_success_criteria_satisfied && !$too_many_submissions) {
                if ($correct_submission) {
                    $message .= $can_resubmit_root_node_question['message'];
                } else {
                    $message .= "However, you have already successfully satisfied the Learning Tree success criteria and can retry the <a id='show-root-assessment' style='cursor: pointer;'>Root Assessment</a> with penalty.";
                    $traffic_light_color = "yellow";
                }
            }

            $response['type'] = 'success';
            $response['message'] = $message;
            $response['correct_submission'] = $correct_submission;
            $response['can_resubmit_root_node_question'] = $learning_tree_success_criteria_satisfied;
            $response['traffic_light_color'] = $traffic_light_color;
            $response['learning_tree_message'] = true;
            $response['add_reset'] = $add_reset;


            DB::commit();
            //don't really care if this gets messed up from the user perspective
            /*try {
               // session()->put('submission_id', md5(uniqid('', true)));
              //  $dataShop->store($remediationSubmission, $data, $assignment, $assignment_question);
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);

            }*/


        } catch
        (Exception $e) {
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
    public function canResubmitRootNodeQuestion($assignment_question_learning_tree, int $user_id, int $assignment_id, int $learning_tree_id): array
    {
        $assignment = Assignment::find($assignment_id);
        $learningTree = LearningTree::find($learning_tree_id);
        $learning_tree_branch_structure = $learningTree->getBranchStructure();
        $branch_and_twig_info = $learningTree->getBranchAndTwigInfo($learning_tree_branch_structure);
        $learning_tree_branch_and_twigs_with_success_with_success_info = $this->getLearningTreeBranchAndTwigWithSuccessInfo($assignment_question_learning_tree, $branch_and_twig_info, $assignment, $user_id, $learning_tree_id);

        return $this->successCriteriaSatisfied($assignment_question_learning_tree, $learning_tree_branch_and_twigs_with_success_with_success_info, $assignment_id, $learning_tree_id);
    }

    /**
     * @throws Exception
     */
    public function successCriteriaSatisfied($assignment_question_learning_tree,
                                             array $learning_tree_branch_and_twigs_with_success_with_success_info): array
    {


        switch ($assignment_question_learning_tree->learning_tree_success_level) {
            case('tree'):
                $success_criteria_satisfied = $this->successCriteriaSatisfiedAtTheTreeLevel($assignment_question_learning_tree, $learning_tree_branch_and_twigs_with_success_with_success_info);
                break;

            case('branch'):
                $success_criteria_satisfied = $this->successCriteriaSatisfiedAtTheBranchLevel($assignment_question_learning_tree, $learning_tree_branch_and_twigs_with_success_with_success_info);
                break;
            default:
                throw new Exception("$assignment_question_learning_tree->learning_tree_success_level is not a valid learning tree success level.");
        }
        return $success_criteria_satisfied;
    }


    /**
     * @throws Exception
     */
    public function successCriteriaSatisfiedAtTheBranchLevel($assignment_question_learning_tree,
                                                             array $learning_tree_branch_and_twigs_with_success_with_success_info): array
    {
        $response['success'] = false;
        $num_successful_branches = 0;
        foreach ($learning_tree_branch_and_twigs_with_success_with_success_info['branches'] as $info) {
            $branch_success = false;
            switch ($assignment_question_learning_tree->learning_tree_success_criteria) {
                case('time based'):
                    $branch_success = $info['time_left'] - 3 <= 0; //since the polling is done every 3 seconds the timer may have hit 0 before sending the info to the server
                    break;
                case('assessment based'):
                    $num_successful_assessments_in_branch = 0;
                    foreach ($info['twigs'] as $twig) {
                        $num_successful_assessments_in_branch += (1 - $twig['question_info']->proportion_correct) < PHP_FLOAT_EPSILON;
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
        }
        $plural = $num_successful_branches !== 1 ? 'es' : '';
        if ($num_successful_branches >= $assignment_question_learning_tree->number_of_successful_branches_for_a_reset) {
            $response['success'] = true;
            $response['message'] = "You have successfully completed $num_successful_branches branch$plural and can retry the <a id='show-root-assessment' style='cursor: pointer;'>Root Assessment</a>.";
        } else {
            $response['message'] = "You have successfully completed $num_successful_branches branch$plural and need to complete $assignment_question_learning_tree->number_of_successful_branches_for_a_reset before you can receive a reset for the Root Assessment.";

        }
        return $response;
    }


    /**
     * @throws Exception
     */
    public function successCriteriaSatisfiedAtTheTreeLevel($assignment_question_learning_tree,
                                                           array $learning_tree_branch_and_twigs_with_success_with_success_info): array
    {
        $response['success'] = false;
        $tree_success = false;
        switch ($assignment_question_learning_tree->learning_tree_success_criteria) {
            case('time based'):
                $tree_success = $learning_tree_branch_and_twigs_with_success_with_success_info['learning_tree']['time_left'] - 3 <= 0;//the server is polled every 3 seconds so this might be off
                $plural = $assignment_question_learning_tree->min_time > 1 ? 's' : '';
                if ($tree_success) {
                    $response['success'] = true;
                    $response['message'] = "You have successfully spent at least $assignment_question_learning_tree->min_time minute$plural in the Learning Tree and can retry the <a id='show-root-assessment' style='cursor: pointer;'>Root Assessment</a>.";
                }
                break;
            case('assessment based'):
                $total_correct_assessments = 0;
                foreach ($learning_tree_branch_and_twigs_with_success_with_success_info['branches'] as $info) {
                    foreach ($info['twigs'] as $twig) {
                        $total_correct_assessments += abs($twig['question_info']->proportion_correct - 1) < PHP_FLOAT_EPSILON;
                        if ($total_correct_assessments >= $assignment_question_learning_tree->min_number_of_successful_assessments) {
                            $tree_success = true;
                        }
                    }
                }
                $plural = $assignment_question_learning_tree->min_number_of_successful_assessments > 1 ? 's' : '';
                if ($tree_success) {
                    $response['success'] = true;
                    $response['message'] = "You have successfully completed at least $assignment_question_learning_tree->min_number_of_successful_assessments assessment$plural in the Learning Tree and will receive a reset for the <a id='show-root-assessment' style='cursor: pointer;'>Root Assessment</a>.";
                }
                break;
            default:
                throw new Exception("$assignment_question_learning_tree->learning_tree_success_criteria is not a valid success criteria.");
        }
        return $response;
    }


    /**
     * @param $assignment_question_learning_tree
     * @param array $branch_and_twig_info
     * @param Assignment $assignment
     * @param int $user_id
     * @param int $learning_tree_id
     * @return array
     */
    public function getLearningTreeBranchAndTwigWithSuccessInfo($assignment_question_learning_tree,
                                                                array $branch_and_twig_info, Assignment $assignment, int $user_id, int $learning_tree_id): array
    {


        $min_time_in_seconds = $assignment_question_learning_tree->min_time * 60;
        $time_lefts_by_branch_id = [];
        foreach ($branch_and_twig_info as $info) {
            $time_lefts_by_branch_id[$info['id']] = $min_time_in_seconds;
        }

        $time_lefts = DB::table('learning_tree_time_lefts')
            ->where('user_id', $user_id)
            ->where('assignment_id', $assignment->id)
            ->where('learning_tree_id', $learning_tree_id)
            ->select('branch_id', 'level', 'time_left')
            ->get();
        $learning_tree_time_left = $min_time_in_seconds;
        foreach ($time_lefts as $time_left) {
            if ($time_left->level === 'tree') {
                $learning_tree_time_left = $time_left->time_left;
            } else {
                if (isset($time_lefts_by_branch_id[$time_left->branch_id])) {
                    $time_lefts_by_branch_id[$time_left->branch_id] = $time_left->time_left;
                }
            }
        }

        $remediation_submissions = DB::table('remediation_submissions')
            ->where('user_id', $user_id)
            ->where('assignment_id', $assignment->id)
            ->where('learning_tree_id', $learning_tree_id)
            ->select('proportion_correct', 'question_id')
            ->get();


        foreach ($remediation_submissions as $remediation_submission) {
            $remediation_submissions_by_question_id[$remediation_submission->question_id] = [
                'proportion_correct' => $remediation_submission->proportion_correct];
        }

        $learning_tree_number_correct = 0;
        foreach ($branch_and_twig_info as $branch_and_twig_info_key => $info) {
            $branch_number_correct = 0;
            foreach ($info['twigs'] as $key => $twig) {
                $info['twigs'][$key]['question_info']->proportion_correct = $remediation_submissions_by_question_id[$twig['question_info']->id]['proportion_correct'] ?? 0;
                if (1 - $info['twigs'][$key]['question_info']->proportion_correct < PHP_FLOAT_EPSILON) {
                    $branch_number_correct++;
                    $learning_tree_number_correct++;
                }
            }
            $branch_and_twig_info[$branch_and_twig_info_key]['time_left'] = $time_lefts_by_branch_id[$info['id']];
            $branch_and_twig_info[$branch_and_twig_info_key]['number_correct'] = $branch_number_correct;
        }
        $learning_tree_branch_and_twig_info = [];
        $learning_tree_branch_and_twig_info['branches'] = $branch_and_twig_info;
        $learning_tree_branch_and_twig_info['learning_tree'] = ['time_left' => $learning_tree_time_left,
            'number_correct' => $learning_tree_number_correct
        ];

        return $learning_tree_branch_and_twig_info;
    }

    /**
     * @param Submission $Submission
     * @param string $technology
     * @param string $oldRemediationSubmission
     * @param $proportion_correct
     * @return bool
     * @throws Exception
     */
    public function correctBeforeButIncorrectNow(Submission $Submission, string $technology, string $oldRemediationSubmission, $proportion_correct)
    {
        $old_proportion_correct = 0;
        switch ($technology) {
            case('h5p'):
                $oldRemediationSubmission = json_decode($oldRemediationSubmission);
                $old_proportion_correct = $Submission->getProportionCorrect('h5p', $oldRemediationSubmission);
                break;
            case('imathas'):
                $old_proportion_correct = $Submission->getProportionCorrect('imathas', $oldRemediationSubmission);
                break;
            case('webwork'):
                $old_proportion_correct = $Submission->getProportionCorrect('webwork', (object)$oldRemediationSubmission);//
                break;
        }

        return (1 - $old_proportion_correct < PHP_FLOAT_EPSILON) && (1 - $proportion_correct >= PHP_FLOAT_EPSILON);
    }

}

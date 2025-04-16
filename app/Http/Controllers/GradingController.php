<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentFile;
use App\AssignmentSyncQuestion;
use App\Console\Commands\Analytics\insertReviewHistories;
use App\Discussion;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\GradingRequest;
use App\JWE;
use App\Question;
use App\RubricCategorySubmission;
use App\RubricPointsBreakdown;
use App\Score;
use App\Submission;
use App\SubmissionFile;
use App\SubmissionScoreOverride;
use App\User;
use App\Webwork;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use App\Traits\DateFormatter;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Traits\LibretextFiles;
use App\Traits\IframeFormatter;
use App\Traits\Seed;
use App\Traits\LatePolicy;


class GradingController extends Controller
{

    use DateFormatter;
    use LibretextFiles;
    use IframeFormatter;
    use Seed;
    use LatePolicy;


    /**
     * @param GradingRequest $request
     * @param Assignment $Assignment
     * @param AssignmentFile $assignmentFile
     * @param User $user
     * @param Score $score
     * @return array
     * @throws Exception
     */
    public function store(GradingRequest $request,
                          Assignment     $Assignment,
                          AssignmentFile $assignmentFile,
                          User           $user,
                          Score          $score): array
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->user_id;
        $assignment = $Assignment->find($assignment_id);


        $authorized = Gate::inspect('storeScore', [$assignmentFile, $user->find($student_user_id), $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();
        $is_discuss_it = false;
        if ($question_id) {
            $question = Question::find($question_id);
            $is_discuss_it = $question->isDiscussIt();
        }
        $extra_validation_response = $this->_extraValidations($request, $question_id, $assignment_id, $student_user_id);
        if ($extra_validation_response['type'] !== 'success') {
            return $extra_validation_response;
        }

        try {
            $text_feedback = $request->textFeedback ? trim($request->textFeedback) : '';

            DB::beginTransaction();
            if ($is_discuss_it) {
                if (!DB::table('submission_files')
                    ->where('user_id', $student_user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->first()) {
                    $submissionFile = new SubmissionFile();
                    $submissionFile->type = 'discuss_it';
                    $submissionFile->original_filename = '';
                    $submissionFile->submission = '';
                    $submissionFile->user_id = $student_user_id;
                    $submissionFile->assignment_id = $assignment_id;
                    $submissionFile->question_id = $question_id;
                    $submissionFile->date_submitted = now();
                    $submissionFile->save();
                }
            }

            $assignment_question = AssignmentSyncQuestion::where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->first();
            $submission_file_data = [
                'text_feedback' => $text_feedback,
                'text_feedback_editor' => $data['text_feedback_editor'],
                'date_graded' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'grader_id' => $request->user()->id,
            ];
            if ($assignment_question->open_ended_submission_type === "0") {
                $submission_file_data['type'] = 'no upload';
                $submission_file_data['original_filename'] = '';
                $submission_file_data['submission'] = '';
                $submission_file_data['date_submitted'] = now();
            }
            SubmissionFile::updateOrCreate(
                [
                    'user_id' => $student_user_id,
                    'assignment_id' => $assignment_id,
                    'question_id' => $question_id,
                ],
                $submission_file_data
            );


            if ($request->file_submission_score !== null) {
                DB::table('submission_files')
                    ->where('user_id', $student_user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->update(['score' => $data['file_submission_score'],
                        'applied_late_penalty' => $request->applied_late_penalty,
                        'date_graded' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'grader_id' => $request->user()->id]);
            }
            if ($request->rubric_points_breakdown) {
                $rubric_items = $request->rubric_points_breakdown;
                $score_input_type = $request->score_input_type;
                switch ($score_input_type) {
                    case('points'):
                        $score_input_type_to_reset = 'percentage';
                        break;
                    case('percentage'):
                        $score_input_type_to_reset = 'points';
                        break;
                    default:
                        throw new Exception("$score_input_type is not a valid score input type.");
                }
                if ($request->special_score) {
                    $original_rubric_with_maxes = $request->original_rubric_with_maxes;
                    $rubric_points_breakdown = json_decode($request->rubric_points_breakdown, 1);
                    $score_input_type_to_unset = $score_input_type === 'points' ? 'percentage' : 'points';
                    $rubric_items = $rubric_points_breakdown['rubric_items'];
                    foreach ($rubric_items as $key => $value) {
                        switch ($request->special_score) {
                            case('full score'):
                                $rubric_items[$key][$score_input_type] = $original_rubric_with_maxes[$key][$score_input_type];
                                break;
                            case('zero score'):
                                $rubric_items[$key][$score_input_type] = 0;
                                break;
                        }
                        unset($rubric_items[$key][$score_input_type_to_unset]);
                    }

                } else {
                    foreach ($rubric_items as $key => $rubric_item) {
                        $rubric_items[$key][$score_input_type_to_reset] = '';
                    }
                    if (+$request->file_submission_score === 0) {
                        foreach ($rubric_items as $key => $value) {
                            $rubric_items[$key][$score_input_type] = 0;
                        }
                    }
                }

                $rubricPointsBreakdown = new RubricPointsBreakdown();
                $points_breakdown = json_encode(['rubric_items' => $rubric_items, 'score_input_type' => $score_input_type]);
                $rubricPointsBreakdown->updateOrCreate([
                    'assignment_id' => $assignment_id,
                    'question_id' => $question_id,
                    'user_id' => $student_user_id],
                    ['points_breakdown' => $points_breakdown]);
            }
            if ($request->question_submission_score !== null) {
                DB::table('submissions')
                    ->where('user_id', $student_user_id)
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->update(['score' => $data['question_submission_score'],
                            'updated_at' => Carbon::now()
                        ]
                    );
            }

            $score->updateAssignmentScore($student_user_id, $assignment_id, $assignment->lms_grade_passback === 'automatic');
            DB::commit();
            $response['type'] = 'success';
            $response['last_graded'] = Carbon::now($request->user()->time_zone)->format('F d, Y \a\t g:i A');
            $response['message'] = 'The score and feedback have been updated.';
            $response['grader_name'] = $request->user()->first_name . ' ' . $request->user()->last_name;
        } catch (Exception $e) {

            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save the information.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param GradingRequest $request
     * @param int $question_id
     * @param int $assignment_id
     * @param int $student_user_id
     * @return array
     */
    private function _extraValidations(GradingRequest $request, int $question_id, int $assignment_id, int $student_user_id): array
    {
        $response['type'] = 'error';
        $max_points = DB::table('assignment_question')
            ->where('question_id', $question_id)
            ->where('assignment_id', $assignment_id)
            ->first()
            ->points;
        $submitted_total_score = 0;
        $submitted_total_score += $request->question_submission_score !== null
            ? $request->question_submission_score
            : 0;
        $submitted_total_score += $request->file_submission_score !== null
            ? $request->file_submission_score
            : 0;
        if ($submitted_total_score > $max_points) {
            $response['message'] = "The total of your Auto-Graded Score and Open-Ended Submission score can't be greater than the total number of points for this question.";
            return $response;
        }
        $current_file_submission = DB::table('submission_files')
            ->where('user_id', $student_user_id)
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->first();

        $current_question_submission = DB::table('submissions')
            ->where('user_id', $student_user_id)
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->first();

        if ($current_question_submission && $request->question_submission_score === null) {
            $response['message'] = "You can't submit an empty score for the auto-graded submission.";
            return $response;
        }

        if ($current_file_submission && $request->file_submission_score === null) {
            $response['message'] = "You can't submit an empty score for the open-ended submission.";
            return $response;
        }


        $current_file_submission_score = $current_file_submission->score ?? null;
        $current_text_feedback = $current_file_submission->text_feedback ?? '';
        if ($current_file_submission_score !== null) {
            $current_file_submission_score = 0 + Helper::removeZerosAfterDecimal($current_file_submission_score);

        }
        $current_question_submission_score = $current_question_submission->score ?? null;
        if ($current_question_submission_score !== null) {
            $current_question_submission_score = 0 + Helper::removeZerosAfterDecimal($current_question_submission_score);
        }
//keeping as == because too confusing with null, ''
        $current_rubric_points_breakdown = DB::table('rubric_points_breakdowns')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->where('user_id', $student_user_id)
            ->first();
        if (!$current_rubric_points_breakdown) {
            $rubric_points_breakdown_updated = true;
        } else {
            $rubric_points_breakdown_updated = $current_rubric_points_breakdown !== $request->rubric_points_breakdown;
        }
        if ($current_file_submission_score == $request->file_submission_score
            && $current_question_submission_score == $request->question_submission_score
            && $current_text_feedback == $request->textFeedback
            && !$rubric_points_breakdown_updated) { // == in case of null vs ''
            $response['type'] = 'info';
            $response['message'] = "Neither the total score nor the overall feedback has been updated.";
            return $response;
        }

        $response['type'] = 'success';
        return $response;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param int $sectionId
     * @param string $gradeView
     * @param SubmissionFile $submissionFile
     * @param Enrollment $enrollment
     * @param Submission $Submission
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param SubmissionScoreOverride $submissionScoreOverride
     * @param Discussion $discussion
     * @return array
     * @throws Exception
     */
    public function index(Request                  $request,
                          Assignment               $assignment,
                          Question                 $question,
                          int                      $sectionId,
                          string                   $gradeView,
                          SubmissionFile           $submissionFile,
                          Enrollment               $enrollment,
                          Submission               $Submission,
                          RubricCategorySubmission $rubricCategorySubmission,
                          SubmissionScoreOverride  $submissionScoreOverride,
                          Discussion               $discussion): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('viewAssignmentFilesByAssignment', [$submissionFile, $assignment, $sectionId]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $domd = new DOMDocument();
            $webwork = new Webwork();
            $JWE = new JWE();

            $course = $assignment->course;
            $role = Auth::user()->role;

            $enrolled_users = $enrollment->getEnrolledUsersByRoleCourseSection($role, $course, $sectionId);
            $ferpa_mode = ((int)request()->cookie('ferpa_mode') === 1 && Auth::user()->id === 5)
                || ($role === 4 && !$assignment->graders_can_see_student_names);
            if ($ferpa_mode) {
                $faker = Factory::create();
                foreach ($enrolled_users as $key => $user) {
                    $enrolled_users[$key]['first_name'] = $faker->firstName;
                    $enrolled_users[$key]['last_name'] = $faker->lastName;
                }
            }

            if ($role === 4 && $sectionId === 0) {
                $access_level_override = $assignment->graders()
                    ->where('assignment_grader_access.user_id', Auth::user()->id)
                    ->first();
                if ($access_level_override && $access_level_override->pivot->access_level) {
                    $enrolled_users = $course->enrolledUsers;
                }
            }


            $user_ids = [];
            foreach ($enrolled_users as $user) {

                $user_ids[] = $user->id;
            }
            sort($user_ids);

            $submission_files_by_user = [];
            $submissions_by_user = [];
            $assign_to_timings_by_user = $assignment->assignToTimingsByUser();
            foreach ($enrolled_users as $key => $enrolled_user) {
                if (!isset($assign_to_timings_by_user[$enrolled_user->id])) {
                    unset($enrolled_users[$key]);
                }
            }
            $rubric_category_submissions = $rubricCategorySubmission->getRubricCategorySubmissionsByUser($assignment);

            $submission_files = $enrolled_users->isNotEmpty() ? $submissionFile->getUserAndQuestionFileInfo($assignment, $gradeView, $enrolled_users, $question->id) : [];

            if ($submission_files) {
                $submission_files = $submission_files[0];//comes back as an array of an array
            }

            foreach ($submission_files as $submission_file) {
                $submission_files_by_user[$submission_file['user_id']] = $submission_file;
            }
            $submissions = $enrolled_users->isNotEmpty() ? $Submission->getAutoGradedSubmissionsByUser($enrolled_users, $assignment, $question) : [];
            foreach ($submissions as $submission) {
                $submissions_by_user[$submission['user_id']] = $submission;
            }
            $grading = [];
            $submission_score_overrides = $submissionScoreOverride
                ->where('assignment_id', $assignment->id)
                ->get();
            foreach ($submission_score_overrides as $submission_score_override) {
                $submission_score_overrides_by_user_id[$submission_score_override->user_id] = $submission_score_override->score;
            }

            $question_revision = DB::table('assignment_question')
                ->join('question_revisions', 'assignment_question.question_revision_id', '=', 'question_revisions.id')
                ->select('question_revisions.*')
                ->where('assignment_question.assignment_id', $assignment->id)
                ->where('assignment_question.question_id', $question->id)
                ->first();
            $question_revision_number = 0;
            if ($question_revision) {
                $question_revision_number = $question_revision->revision_number;
            }

            ///open-ended stuff
            $non_technology_iframe_src = $question['non_technology'] ? $this->getHeaderHtmlIframeSrc($question, $question_revision_number) : '';

            $seeds = DB::table('seeds')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();
            $user_ids_with_seeds = [];
            foreach ($seeds as $seed) {
                $user_ids_with_seeds[] = $seed->user_id;
            }

            if (!in_array($question->technology, ['text', 'h5p'])) {
                foreach ($enrolled_users as $user) {
                    if (!in_array($user->id, $user_ids_with_seeds)) {
                        $seed = $this->createSeedByTechnologyAssignmentAndQuestion($assignment, $question);
                        DB::table('seeds')->insert([
                            'assignment_id' => $assignment->id,
                            'question_id' => $question->id,
                            'user_id' => $user->id,
                            'seed' => $seed,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                    }
                }
            }

            $submissions = DB::table('submissions')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();
            foreach ($submissions as $submission) {
                $submissions_by_user_id[$submission->user_id] = $submission;
            }

            $seeds = DB::table('seeds')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();
            foreach ($seeds as $seed) {
                $seeds_by_user_id[$seed->user_id] = $seed->seed;
            }

            $extensions = DB::table('extensions')->where('assignment_id', $assignment->id)->get();
            foreach ($extensions as $extension) {
                $extensions_by_user_id[$extension->user_id] = $extension->extension;
            }
            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $points = $assignment_question->points;


            $discussions_by_user_id = $discussion->getByAssignmentQuestionMediaUploadId($assignment, $question, 0)['discussions_by_user_id'];
            $discussions = $discussion->getByAssignmentQuestionMediaUploadId($assignment, $question, 0)['discussions'];
            foreach ($enrolled_users as $user) {
                $question = Question::find($question->id);//something was happening with webwork in that the question was changing on each iteration
                $seed = $seeds_by_user_id[$user->id] ?? '';
                $return_student_info = ($submission_files_by_user[$user->id]['submission_status'] === $gradeView || $gradeView === 'allStudents');

                //non-json question
                $custom_claims = [];
                $sessionJWT = '';
                $technology_iframe = '';
                $late_penalty_percent = 0;
                $hint_penalty_percent = 0;
                $number_of_attempts_penalty_percent = 0;
                $submission = $submissions_by_user_id[$user->id] ?? null;
                if ($submission) {
                    $decoded_submission = json_decode($submission->submission, 1);
                    if ($decoded_submission && isset($decoded_submission['sessionJWT'])) {
                        $sessionJWT = $decoded_submission['sessionJWT'];
                    }

                    $hint_penalty_percent = $Submission->getHintPenalty($submission->user_id, $assignment, $submission->question_id);
                    $num_deductions_to_apply = $submission->submission_count - 1;
                    $number_of_attempts_penalty_percent = $num_deductions_to_apply * $assignment->number_of_allowed_attempts_penalty;

                    if (in_array($assignment->late_policy, ['marked late', 'deduction'])) {
                        $extension = $extensions_by_user_id[$user->id] ?? null;
                        $late_file_submission = $this->isLateSubmissionGivenExtensionForMarkedLatePolicy($extension, $assign_to_timings_by_user[$user->id]->due, $submission->updated_at);
                        if ($late_file_submission) {
                            $late_penalty_percent = $Submission->latePenaltyPercentGivenUserId($user->id, $assignment, Carbon::parse($submission->updated_at));
                        }
                    }
                }

                if ($return_student_info && in_array($question->technology, ['h5p', 'webwork', 'imathas'])) {
                    if ($question->technology === 'imathas' && $submission) {
                        $custom_claims['stuanswers'] = $Submission->getStudentResponse($submission, 'imathas');
                        $custom_claims['raw'] = [];
                        $custom_claims['raw'] = json_decode($submission->submission)->raw ?
                            json_decode($submission->submission)->raw
                            : [];
                    }
                    $technology_src_and_problemJWT = $question->getTechnologySrcAndProblemJWT($request, $assignment, $question, $seed, false, $domd, $JWE, $custom_claims);
                    $technology_src = $technology_src_and_problemJWT['technology_src'];
                    $problemJWT = $technology_src_and_problemJWT['problemJWT'];
                    if ($technology_src) {
                        $grading[$user->id]['iframe_id'] = $this->createIframeId();
                        //don't return if not available yet!
                        $technology_iframe = $this->formatIframeSrc($question->technology_iframe, $grading[$user->id]['iframe_id'], $problemJWT, $sessionJWT);


                    }
                }

                ///json-question
                $qti_json = null;
                if ($question->qti_json) {

                    $student_response = isset($submissions_by_user_id[$user->id])
                        ? $Submission->getStudentResponse($submissions_by_user_id[$user->id], 'qti')
                        : '';
                    $qti_json = $question->formatQtiJson('question_json', $question->qti_json, $seed, true, $student_response);
                }


                if ($return_student_info) {
                    $grading[$user->id] = [];
                    $grading[$user->id]['student'] = [
                        'name' => "$user->first_name $user->last_name",
                        'email' => $user->email,
                        'student_id' => $user->student_id,
                        'user_id' => $user->id];
                    //open_ended
                    $grading[$user->id]['non_technology_iframe_src'] = $non_technology_iframe_src;
                    $grading[$user->id]['technology_iframe'] = $technology_iframe;
                    $grading[$user->id]['submission_array'] = isset($submissions_by_user_id[$user->id])
                        ? $Submission->getSubmissionArray($assignment, $question, $submissions_by_user_id[$user->id], false)
                        : [];


                    $grading[$user->id]['penalties'] = [[
                        'text' => 'Late Penalty:',
                        'percent' => $late_penalty_percent,
                        'points' => $this->_getPointsFromPercent($points, $late_penalty_percent)
                    ],
                        ['text' => 'Number of Attempts Penalty:',
                            'percent' => $number_of_attempts_penalty_percent,
                            'points' => $this->_getPointsFromPercent($points, $number_of_attempts_penalty_percent)
                        ], [
                            'text' => 'Hint Penalty:',
                            'percent' => $hint_penalty_percent,
                            'points' => $this->_getPointsFromPercent($points, $hint_penalty_percent)
                        ]
                    ];


                    $grading[$user->id]['qti_json'] = $qti_json;
                    $grading[$user->id]['open_ended_submission'] = $submission_files_by_user[$user->id] ?? false;
                    $grading[$user->id]['auto_graded_submission'] = $submissions_by_user[$user->id] ?? false;
                    $grading[$user->id]['rubric_category_submission'] = $rubric_category_submissions[$user->id] ?? false;
                    $grading[$user->id]['last_graded'] = $this->_getLastGraded($grading[$user->id]);
                    $grading[$user->id]['submission_score_override'] = $submission_score_overrides_by_user_id[$user->id] ?? null;
                    $grading[$user->id]['submission_status'] = $submission_files_by_user[$user->id]['submission_status'] ?? null;
                }
            }

            $is_auto_graded = $question->technology !== 'text';
            $is_open_ended = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();

            $response['is_auto_graded'] = $is_auto_graded;
            $response['show_auto_graded_submission'] = $is_auto_graded && $question->technology === 'h5p';
            $response['rubric'] = $assignment_question->custom_rubric ?: $question->rubric;
            $response['technology'] = $question->technology;
            $response['is_open_ended'] = $is_open_ended;
            $response['algorithmic'] = $webwork->algorithmicSolution($question);
            $response['type'] = 'success';
            $response['grading'] = array_values($grading);
            $response['message'] = "Your view has been updated.";
            $response['graders_can_see_student_names'] = (bool)$assignment->graders_can_see_student_names;
            $response['discussions'] = $discussions;
            $response['discussions_by_user_id'] = $discussions_by_user_id;
            $response['discuss_it'] = $question->isDiscussIt();

        } catch
        (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the file submissions for this assignment.  Please try again or contact us for assistance.";
        }

        return $response;

    }

    private function _getLastGraded($user_grading)
    {
        $open_ended_date_graded = $user_grading['open_ended_submission'] && $user_grading['open_ended_submission']['date_graded']
            ? new Carbon($user_grading['open_ended_submission']['date_graded'])
            : false;
        $auto_graded_date_graded = $user_grading['auto_graded_submission']
            ? new Carbon($user_grading['auto_graded_submission']['updated_at'])
            : false;


        if ($open_ended_date_graded && $auto_graded_date_graded) {
            $last_graded = $open_ended_date_graded > $auto_graded_date_graded ? $open_ended_date_graded : $auto_graded_date_graded;

        } elseif ($open_ended_date_graded && !$auto_graded_date_graded) {
            $last_graded = $open_ended_date_graded;

        } elseif (!$open_ended_date_graded && $auto_graded_date_graded) {
            $last_graded = $auto_graded_date_graded;

        } else {
            $last_graded = false;
        }
        if ($last_graded) {
            $last_graded = $last_graded->setTimezone(Auth::user()->time_zone)->format('F d, Y \a\t g:i A');
        }
        return $last_graded;
    }

    private function _getPointsFromPercent($points, $penalty_percent)
    {
        return floatval(Helper::removeZerosAfterDecimal(round(floatval($points) * floatval($penalty_percent / 100), 4)));

    }

}

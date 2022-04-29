<?php

namespace App\Http\Controllers;

use App\AssignmentGroup;
use App\Enrollment;
use App\Extension;
use App\Helpers\Helper;
use App\Http\Requests\UpdateScoresRequest;
use App\Jobs\ProcessPassBackByUserIdAndAssignment;
use App\LtiGradePassback;
use App\Score;
use App\Course;
use App\Solution;
use App\SubmissionFile;
use App\User;
use App\Assignment;
use App\Submission;
use App\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Traits\Statistics;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr\Assign;

ini_set('max_execution_time', 300);

class ScoreController extends Controller
{
    use Statistics;


    public function getFerpaMode(Request $request)
    {
        $response['type'] = 'error';
        try {
            $ferpa_mode = $request->hasCookie('ferpa_mode')
                ? $request->cookie('ferpa_mode')
                : 0;
            $response['ferpa_mode'] = $ferpa_mode;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $response['message'] = 'We were unable to retrieve your FERPA mode coookie.';
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;
    }

    /**
     * @param UpdateScoresRequest $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Score $score
     * @param SubmissionFile $submissionFile
     * @param Submission $submission
     * @return array
     * @throws Exception
     */
    public function overTotalPoints(UpdateScoresRequest $request,
                                    Assignment          $assignment,
                                    Question            $question,
                                    Score               $score,
                                    SubmissionFile      $submissionFile,
                                    Submission          $submission)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('overTotalPoints', [$score, $assignment]);
        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $new_score = $request->new_score;
            $apply_to = $request->apply_to;
            $type = $request->type;
            $total_points = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->pluck('points')
                ->first();
            $auto_graded_submissions = $apply_to
                ? $submission->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->whereIn('user_id', $request->user_ids)
                    ->get()
                : $submission->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->whereNotIn('user_id', $request->user_ids)
                    ->get();

            $submission_files = $apply_to
                ? $submissionFile->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->where('type', '<>', 'a')
                    ->whereIn('user_id', $request->user_ids)
                    ->get()
                : $submissionFile->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->where('type', '<>', 'a')
                    ->whereNotIn('user_id', $request->user_ids)
                    ->get();
            $auto_graded_submissions_by_user = [];
            $submission_files_by_user = [];


            $user_ids = [];
            foreach ($auto_graded_submissions as $auto_graded_submission) {
                $auto_graded_submissions_by_user[$auto_graded_submission->user_id] = $type === 'Auto-graded'
                    ? $new_score
                    : $auto_graded_submission->score;
                $user_ids[] = $auto_graded_submission->user_id;
            }

            foreach ($submission_files as $submission_file) {
                $submission_files_by_user[$submission_file->user_id] = $type === 'Open-ended'
                    ? $new_score
                    : $submission_file->score;
                $user_ids[] = $submission_file->user_id;
            }
            $user_ids = array_unique($user_ids);
            $total_scores_by_user = [];
            foreach ($user_ids as $user_id) {
                $total_scores_by_user[$user_id] = 0;
                $total_scores_by_user[$user_id] += $auto_graded_submissions_by_user[$user_id] ?? 0;
                $total_scores_by_user[$user_id] += $submission_files_by_user[$user_id] ?? 0;
            }
            $num_over_max = 0;
            foreach ($total_scores_by_user as $total_score_by_user) {
                if ($total_score_by_user > $total_points) {
                    $num_over_max++;
                }
            }

            $response['type'] = 'success';
            $response['num_over_max'] = $num_over_max;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error overriding these assignment scores.  Please try again or contact us for assistance.";
        }
        return $response;


    }


    public function overrideScores(Request $request, Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('overrideScores', [$score, $assignment, $request->overrideScores]);
        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $override_scores = $request->overrideScores;
            $lti_launches_by_user_id = $assignment->ltiLaunchesByUserId();
            $ltiGradePassBack = new LtiGradePassback();
            DB::beginTransaction();
            foreach ($override_scores as $override_score) {
                if ($override_score['override_score'] !== null) {
                    $user_id = $override_score['user_id'];
                    $override_score = $override_score['override_score'];
                    Score::updateOrCreate(
                        ['assignment_id' => $assignment->id,
                            'user_id' => $user_id],
                        ['score' => $override_score]);

                    if (isset($lti_launches_by_user_id[$user_id])) {
                        $ltiGradePassBack->initPassBackByUserIdAndAssignmentId($override_score, $lti_launches_by_user_id[$user_id]);
                    }
                }
            }
            $response['type'] = 'success';
            $response['message'] = 'The scores have been updated.';
            DB::commit();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error overriding these assignment scores.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function uploadOverrideScores(Request $request, Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        try {
            $overrideScores = $request->file('overrideScoresFile')->store("override-scores/$assignment->id", 'local');
            $csv_file = Storage::disk('local')->path($overrideScores);
            $current_scores = $score->where('assignment_id', $assignment->id)->get();

            foreach ($current_scores as $current_score) {
                $current_scores_by_user_id[$current_score->user_id] = $current_score->score;
            }
            $override_scores = Helper::csvToArray($csv_file);
            $override_score_errors = [];
            foreach ($override_scores as $override_score) {
                $score = $this->fixCSV($override_score['Score'], true);
                if ($score !== '' && (!is_numeric($score) || $score < 0)) {
                    $override_score_errors[] = $override_score['Name'];
                }
            }
            if ($override_score_errors) {
                $response['override_score_errors'] = $override_score_errors;
                return $response;
            }


            if (!(isset($override_scores[0]['UserId'])
                && isset($override_scores[0]['Name'])
                && isset($override_scores[0]['Score']))) {
                $response['message'] = "Your csv file should have UserId, Name, and Score as the first row.";
                return $response;
            }
            $from_to_scores = [];
            foreach ($override_scores as $override_score) {
                $user_id = $this->fixCSV($override_score['UserId']);
                $from_to_scores[] = ['user_id' => $user_id,
                    'name' => $this->fixCSV($override_score['Name']),
                    'override_score' => $this->fixCSV($override_score['Score'], true),
                    'current_score' => $current_scores_by_user_id[$user_id] ?? '-'];
            }
            $response['from_to_scores'] = $from_to_scores;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error uploading your scores.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function fixCSV($value, $fix_dash = false)
    {

        $value = $fix_dash ? str_replace('-', '', $value) : $value;
        return str_replace(['"', '='], ['', ''], $value);
    }

    public function getTotalPointsByAssignmentId($assignments, array $assignment_ids)
    {


        foreach ($assignments as $assignment) {
            if ($assignment->number_of_randomized_assessments) {
                $randomized_assignment_total_points[$assignment->id] = $assignment->default_points_per_question * $assignment->number_of_randomized_assessments;
            }
        }
        $total_points_by_assignment_id = [];
        $adapt_total_points = DB::table('assignment_question')
            ->selectRaw('assignment_id, sum(points) as sum')
            ->whereIn('assignment_id', $assignment_ids)
            ->groupBy('assignment_id')
            ->get();
        $external_total_points = DB::table('assignments')
            ->whereIn('id', $assignment_ids)
            ->where('source', 'x')
            ->get();

        foreach ($adapt_total_points as $key => $value) {
            $total_points_by_assignment_id[$value->assignment_id] = isset($randomized_assignment_total_points[$value->assignment_id])
                ? $randomized_assignment_total_points[$value->assignment_id]
                : $value->sum;
        }
        foreach ($external_total_points as $key => $value) {
            $total_points_by_assignment_id[$value->id] = $value->external_source_points;
        }

        return $total_points_by_assignment_id;
    }

    /**
     * @param $enrolled_user_ids
     * @param int $course_id
     * @param $include_in_weighted_average_by_assignment_id_and_user_id
     * @return array[]
     */
    public function getAssignmentGroupWeights($enrolled_user_ids, int $course_id, $include_in_weighted_average_by_assignment_id_and_user_id)
    {
        $assignment_groups_by_assignment_id = [];
        $assignment_group_weights_info = [];

        $assignment_group_weights = DB::table('assignments')
            ->join('assignment_group_weights', 'assignments.assignment_group_id', '=', 'assignment_group_weights.assignment_group_id')
            ->where('assignment_group_weights.course_id', $course_id)
            ->where('assignments.course_id', $course_id)
            ->select('assignments.id', 'assignments.assignment_group_id', 'assignment_group_weights.assignment_group_weight')
            ->get();
//create arrays for assignment_group_ids, weights, and counts


        foreach ($assignment_group_weights as $key => $value) {
            foreach ($enrolled_user_ids as $user_id) {
                $assignment_groups_by_assignment_id[$value->id] = $value->assignment_group_id;
                if (!isset($assignment_group_weights_info[$value->assignment_group_id])) {
                    $assignment_group_weights_info[$value->assignment_group_id] = [];
                }
                if (isset($assignment_group_weights_info[$value->assignment_group_id][$user_id])) {
                    $assignment_group_weights_info[$value->assignment_group_id][$user_id]['count'] = $include_in_weighted_average_by_assignment_id_and_user_id[$value->id][$user_id]
                        ? $assignment_group_weights_info[$value->assignment_group_id][$user_id]['count'] + 1
                        : $assignment_group_weights_info[$value->assignment_group_id][$user_id]['count'];
                } else {
                    $assignment_group_weights_info[$value->assignment_group_id][$user_id] = [];
                    $assignment_group_weights_info[$value->assignment_group_id][$user_id]['weight'] = $value->assignment_group_weight;
                    $assignment_group_weights_info[$value->assignment_group_id][$user_id]['count'] = $include_in_weighted_average_by_assignment_id_and_user_id[$value->id][$user_id] ? 1 : 0;
                }
            }
        }
        return [$assignment_group_weights_info, $assignment_groups_by_assignment_id];
    }

    /**
     * @param Course $course
     * @param $scores
     * @param array $assignment_groups_by_assignment_id
     * @param array $total_points_by_assignment_id
     * @param array $include_in_weighted_average_by_assignment_id_and_user_id
     * @return array[]
     */
    public function getScoresByUserIdAndAssignment(Course $course, $scores, array $assignment_groups_by_assignment_id, array $total_points_by_assignment_id, array $include_in_weighted_average_by_assignment_id_and_user_id): array
    {
        //organize the scores by user_id and assignment
        $scores_by_user_and_assignment = [];
        $proportion_scores_by_user_and_assignment_group = [];
        $sum_of_scores_by_user_and_assignment_group = [];
        $fake_student_ids = $course->fakeStudentIds();
        foreach ($scores as $score) {
            $user_id = $score->user_id;
            if (in_array($user_id, $fake_student_ids)) {
                continue;
            }
            $assignment_id = $score->assignment_id;
            $scores_by_user_and_assignment[$user_id][$assignment_id] = Helper::removeZerosAfterDecimal(Round($score->score, 2));
            $group_id = $assignment_groups_by_assignment_id[$assignment_id];
            //init if needed
            $proportion_scores_by_user_and_assignment_group[$user_id][$group_id] = $proportion_scores_by_user_and_assignment_group[$user_id][$group_id] ?? 0;
            $sum_of_scores_by_user_and_assignment_group[$user_id][$group_id] = $sum_of_scores_by_user_and_assignment_group[$user_id][$group_id] ?? 0;

            if (!isset($total_points_by_assignment_id[$assignment_id])) {
                $total_points_by_assignment_id[$assignment_id] = 0; //if they have an ADAPT assignment without questions
            }
            $score_as_proportion = (($total_points_by_assignment_id[$assignment_id]) <= 0)//total_points_by_assignment can be 0.00
                ? 0
                : $score->score / $total_points_by_assignment_id[$assignment_id];

            $proportion_scores_by_user_and_assignment_group[$user_id][$group_id] += $include_in_weighted_average_by_assignment_id_and_user_id[$assignment_id][$user_id]
                ? $score_as_proportion
                : 0;

            $sum_of_scores_by_user_and_assignment_group[$user_id][$group_id] += (($total_points_by_assignment_id[$assignment_id]) <= 0)//total_points_by_assignment can be 0.00
                ? 0
                : $score->score * $include_in_weighted_average_by_assignment_id_and_user_id[$assignment_id][$user_id];
            $sum_of_scores_by_user_and_assignment_group[$user_id][$group_id] = Helper::removeZerosAfterDecimal(Round($sum_of_scores_by_user_and_assignment_group[$user_id][$group_id], 2));
        }
        return [$scores_by_user_and_assignment, $proportion_scores_by_user_and_assignment_group, $sum_of_scores_by_user_and_assignment_group];
    }

    public function getFinalWeightedScoresAndLetterGrades(Course     $course,
                                                          Collection $enrolled_users,
                                                          array      $proportion_scores_by_user_and_assignment_group,
                                                          array      $assignment_group_weights_info)
    {
        $letter_grades = explode(',', $course->finalGrades->letter_grades);
        $letter_grades_array = [];

        for ($i = 0; $i < count($letter_grades) / 2; $i++) {
            $letter_grades_array[] = ['min_score' => $letter_grades[2 * $i], 'letter_grade' => $letter_grades[2 * $i + 1]];
        }

        $final_weighted_scores = [];
        $letter_grades = [];
        $extra_credit = [];
        foreach ($course->extraCredits as $key => $value) {
            $extra_credit[$value->user_id] = $value->extra_credit;
        }


        $extra_credit_group_id = DB::table('assignment_groups')->where('assignment_group', 'Extra Credit')
            ->pluck('id')
            ->first();
        $final_weighted_scores_without_extra_credit = [];

        foreach ($enrolled_users as $key => $user) {
            $final_weighted_scores[$user->id] = 0;
            $final_weighted_scores_without_extra_credit[$user->id] = 0;
            if (isset($proportion_scores_by_user_and_assignment_group[$user->id])) {

                foreach ($proportion_scores_by_user_and_assignment_group[$user->id] as $group_id => $group_score) {
                    $final_weighted_scores[$user->id] += $assignment_group_weights_info[$group_id][$user->id]['count']
                        ? $assignment_group_weights_info[$group_id][$user->id]['weight'] * $group_score / $assignment_group_weights_info[$group_id][$user->id]['count']
                        : 0;

                    $final_weighted_scores_without_extra_credit[$user->id] += $assignment_group_weights_info[$group_id][$user->id]['count'] && ($group_id !== $extra_credit_group_id)
                        ? $assignment_group_weights_info[$group_id][$user->id]['weight'] * $group_score / $assignment_group_weights_info[$group_id][$user->id]['count']
                        : 0;

                }
            }
            if (!isset($extra_credit[$user->id])) {
                $extra_credit[$user->id] = 0;
            }

            $final_weighted_scores[$user->id] += $extra_credit[$user->id];

        }

        foreach ($enrolled_users as $key => $user) {
            $score = Round($final_weighted_scores[$user->id], 2);
            $final_weighted_scores_without_extra_credit[$user->id] = Round($final_weighted_scores_without_extra_credit[$user->id], 2);
            $final_weighted_scores[$user->id] = $score . '%';
            $letter_grades[$user->id] = $this->getLetterGradeBasedOnScore($score, $letter_grades_array, $course->finalGrades->round_scores);
        }

        return ['final_weighted_scores' => $final_weighted_scores,
            'final_weighted_scores_without_extra_credit' => $final_weighted_scores_without_extra_credit,
            'letter_grades' => $letter_grades,
            'extra_credit' => $extra_credit];
    }

    public function getAssignmentIds($assignments)
    {
        return $assignments->map(function ($assignment) {
            return collect($assignment->toArray())
                ->all()['id'];
        })->toArray();
    }

    public function getLetterGradeBasedOnScore($score, $letter_grades_array, $round_scores)
    {
        foreach ($letter_grades_array as $letter_grade_key => $letter_grade_value) {
            $score = $round_scores ? Round($score, 0) : $score;
            if ($score >= $letter_grade_value['min_score']) {
                return $letter_grade_value['letter_grade'];
            }
        }
        return 'Letter grade error';
    }

    /**
     * @param Course $course
     * @param array $assignment_ids
     * @param array $enrolled_users
     * @param $assignments
     * @param array $extensions
     * @param array $extra_credit
     * @param array $final_weighted_scores
     * @param array $letter_grades
     * @param array $final_weighted_scores_without_extra_credit
     * @param array $scores_by_user_and_assignment
     * @param array $total_points_by_assignment_id
     * @return array
     */
    public function getFinalTableInfo(Course $course,
                                      array  $assignment_ids,
                                      array  $enrolled_users,
                                             $assignments,
                                      array  $extensions,
                                      array  $extra_credit,
                                      array  $final_weighted_scores,
                                      array  $letter_grades,
                                      array  $final_weighted_scores_without_extra_credit,
                                      array  $scores_by_user_and_assignment,
                                      array  $total_points_by_assignment_id): array
    {
        {
            $with_download_rows = Auth::user()->role === 2;
            $extra_credit_assignment_id = max($assignment_ids) + 1;
            $weighted_score_assignment_id = $extra_credit_assignment_id + 1;
            $z_score_assignment_id = $weighted_score_assignment_id + 1;
            $letter_grade_assignment_id = $z_score_assignment_id++;

            $course_average = count($final_weighted_scores_without_extra_credit) ? array_sum($final_weighted_scores_without_extra_credit) / count($final_weighted_scores_without_extra_credit) : 0;
            $course_std_dev = $this->stats_standard_deviation($final_weighted_scores_without_extra_credit);
            $mean_and_std_dev_info = ['average' => $course_average, 'std_dev' => $course_std_dev];


            //now fill in the actual scores
            $rows = [];
            $download_rows = [];
            $download_fields = new \stdClass();
            $assignment_scores = [];
            foreach ($assignments as $assignment) {
                $assignment_scores[$assignment->id] = [];
            }
            foreach ($enrolled_users as $user_id => $user_info) {
                $columns = [];
                if ($with_download_rows) {
                    $download_row_data = [
                        'first_name' => $user_info['first_name'],
                        'last_name' => $user_info['last_name'],
                        'course_section' => $user_info['course_section'],
                        'crn' => $user_info['crn'],
                        'term' => $course->term,
                        'email' => $user_info['email'],
                        'student_id' => $user_info['student_id']];
                }
                foreach ($assignments as $assignment) {
                    if (isset($scores_by_user_and_assignment[$user_id][$assignment->id])) {
                        $assignment_scores[$assignment->id][] = $scores_by_user_and_assignment[$user_id][$assignment->id];
                    }
                    $default_score = '-';
                    $score = $scores_by_user_and_assignment[$user_id][$assignment->id] ?? $default_score;
                    if (isset($extensions[$user_id][$assignment->id])) {
                        $score .= ' (E)';
                    }
                    $columns[$assignment->id] = $score;
                    if ($with_download_rows) {
                        $download_row_data["{$assignment->id}"] = str_replace(' (E)', '', $score);//get rid of the extension info
                    }
                }


                $columns[$extra_credit_assignment_id] = $extra_credit[$user_id];
                $columns[$weighted_score_assignment_id] = $final_weighted_scores[$user_id];
                $columns[$z_score_assignment_id] = $this->computeZScore($final_weighted_scores_without_extra_credit[$user_id], $mean_and_std_dev_info);

                $columns[$letter_grade_assignment_id] = $letter_grades[$user_id];
                $download_row_data[$extra_credit_assignment_id] = $extra_credit[$user_id];
                if ($with_download_rows) {
                    $download_row_data[$weighted_score_assignment_id] = $final_weighted_scores[$user_id];
                    $download_row_data[$z_score_assignment_id] = $columns[$z_score_assignment_id];
                    $download_row_data[$letter_grade_assignment_id] = $letter_grades[$user_id];
                }

                $columns['name'] = $user_info['name'];
                $columns['email'] = $user_info['email'];
                $columns['userId'] = $user_id;
                $download_rows[] = $download_row_data;
                $rows[] = $columns;
            }

            $fields = [['key' => 'name',
                'label' => 'Name',
                'sortable' => true,
                'stickyColumn' => true,
                'isRowHeader' => true],
                ['key' => 'email',
                    'label' => 'Email',
                    'sortable' => true,
                    'stickyColumn' => false]];
            if ($with_download_rows) {
                $download_fields->Term = 'term';
                $download_fields->CRN = 'crn';
                $download_fields->{'First Name'} = 'first_name';
                $download_fields->{'Last Name'} = 'last_name';
                $download_fields->Email = 'email';
                $download_fields->{'Student ID'} = 'student_id';
                $download_fields->{'Course - Section'} = 'course_section';
                $reserved_names = ['Term', 'CRN', 'First Name', 'Last Name', 'Email', 'Student Id', 'Course - Section',
                    'Extra Credit', 'Weighted Score', 'Z-Score', 'Letter Grade'];
            }

            foreach ($assignments as $assignment) {
                $mean = count($assignment_scores[$assignment->id]) ? Round(array_sum($assignment_scores[$assignment->id]) / count($assignment_scores[$assignment->id]), 2) : 'N/A';
                $points = Helper::removeZerosAfterDecimal(Round(0 + ($total_points_by_assignment_id[$assignment->id] ?? 0), 2));
                $field = ['key' => "$assignment->id",
                    'name_only' => $assignment->name,
                    'assignment_id' => $assignment->id,
                    'points' => $points,
                    'mean' => $mean,
                    'not_included' => !$assignment->include_in_weighted_average,
                    'sortable' => true,
                    'tdClass' => 'text-center'];
                if ($with_download_rows) {
                    if (in_array($assignment->name, $reserved_names)) {
                        $assignment->name .= ' ';
                    }
                    $download_fields->{$assignment->name} = $assignment->id;
                }
                $fields[] = $field;
            }
            $fields[] = ['key' => "$extra_credit_assignment_id",
                'label' => 'Extra Credit',
                'name_only' => 'Extra Credit',
                'sortable' => true,
                'tdClass' => 'text-center',
                'thClass' => 'text-center'];
            $fields[] = ['key' => "$weighted_score_assignment_id",
                'label' => 'Weighted Score',
                'name_only' => 'Weighted Score',
                'sortable' => true,
                'tdClass' => 'text-center',
                'thClass' => 'text-center'];
            $fields[] = ['key' => "$z_score_assignment_id",
                'label' => 'Z-Score',
                'name_only' => 'Z-Score',
                'sortable' => true,
                'tdClass' => 'text-center',
                'thClass' => 'text-center'];
            $fields[] = ['key' => "$letter_grade_assignment_id",
                'label' => 'Letter Grade',
                'name_only' => 'Letter Grade',
                'sortable' => true,
                'tdClass' => 'text-center',
                'thClass' => 'text-center'];
            if ($with_download_rows) {
                $download_fields->{"Extra Credit"} = $extra_credit_assignment_id;
                $download_fields->{"Weighted Score"} = $weighted_score_assignment_id;
                $download_fields->{"Z-Score"} = $z_score_assignment_id;
                $download_fields->{"Letter Grade"} = $letter_grade_assignment_id;
            }

            return [$rows, $fields, $download_rows, $download_fields, $extra_credit_assignment_id, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id];

        }
    }

    public function getScoresByAssignmentAndQuestion(Request $request, Assignment $assignment, Question $question, SubmissionFile $submissionFile, Submission $submission, Score $Score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getScoreByAssignmentAndQuestion', [$Score, $assignment]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $scores = [];

            $submissionFiles = $submissionFile->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();
            if ($submissionFiles->isNotEmpty()) {
                foreach ($submissionFiles as $key => $submission_file) {
                    $scores[$submission_file->user_id] = $submission_file->score;
                }
            }

            $submissions = $submission->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->get();

            if ($submissions->isNotEmpty()) {
                foreach ($submissions as $key => $submission) {
                    $submission_file_score = $scores[$submission->user_id] ?? 0;
                    $scores[$submission->user_id] = $submission_file_score + $submission->score;
                }
            }

            $response['type'] = 'success';
            $response['scores'] = array_values($scores);
            return $response;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the scores summary.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getAssignmentQuestionScoresByUser(Assignment $assignment,
                                                      Score      $score,
                                                      Enrollment $enrollment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentQuestionScoresByUser', [$score, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $enrolled_users = [];
            $viewable_users = $enrollment->getEnrolledUsersByRoleCourseSection(request()->user()->role, $assignment->course, 0);

            if ($viewable_users->isNotEmpty()) {
                foreach ($viewable_users as $value) {
                    $sorted_users[] = ['name' => "{$value->last_name}, {$value->first_name}",
                        'id' => $value->id];
                }

                usort($sorted_users, function ($a, $b) {
                    return $a['name'] <=> $b['name'];
                });

                foreach ($sorted_users as $value) {
                    $enrolled_users[$value['id']] = $value['name'];
                }
            }

            $file_submission_scores = [];
            foreach ($assignment->fileSubmissions as $key => $value) {
                $file_submission_scores[$value->user_id][$value->question_id] = $value->score;
            }
            $submission_scores = [];
            foreach ($assignment->submissions as $key => $value) {
                $submission_scores[$value->user_id][$value->question_id] = $value->score;
            }
            $points = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->select('question_id', 'points')
                ->get();
            $total_points = 0;
            foreach ($points as $key => $value) {
                $total_points += $value->points;
                $total_points_by_question_id[$value->question_id] = $value->points;
            }

            $questions = $assignment->questions;
            $rows = [];

            foreach ($enrolled_users as $user_id => $name) {
                $columns = [];
                $assignment_score = 0;
                foreach ($questions as $question) {
                    $score = '-';
                    if (isset($submission_scores[$user_id][$question->id]) || isset($file_submission_scores[$user_id][$question->id])) {
                        $score = 0;
                        $score = $score
                            + ($submission_scores[$user_id][$question->id] ?? 0)
                            + ($file_submission_scores[$user_id][$question->id] ?? 0);
                        $assignment_score += $score;
                    }
                    $columns[$question->id] = $score;
                }
                $columns['name'] = $name;
                if ($total_points) {
                    $columns['percent_correct'] = Round(100 * $assignment_score / $total_points, 1) . '%';
                    $columns['total_points'] = $assignment_score;
                }
                $columns['userId'] = $user_id;
                $rows[] = $columns;

            }

            $fields = [['key' => 'name',
                'label' => 'Name',
                'sortable' => true,
                'isRowHeader' => true,
                'stickyColumn' => true,
                'thStyle' => 'max-width: 100px']];

            $i = 1;
            foreach ($questions as $key => $question) {
                $points = $total_points_by_question_id[$question->id];
                $field = ['key' => "$question->id",
                    'isRowHeader' => true,
                    'label' => "Q$i ($points)",
                    'sortable' => true];
                $i++;
                array_push($fields, $field);
            }

            if ($total_points) {
                array_push($fields, ['key' => 'total_points',
                    'sortable' => true,
                    'isRowHeader' => true]);
                array_push($fields, ['key' => 'percent_correct',
                    'sortable' => true,
                    'isRowHeader' => true]);
            }


            $response['type'] = 'success';
            $response['rows'] = $rows;
            $response['fields'] = $fields;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the scores for each question.  Please try again or contact us for assistance.";

        }
        return $response;


    }

    /**
     * @param Course $course
     * @param Extension $extension
     * @param Score $Score
     * @param Submission $Submission
     * @param Solution $Solution
     * @param AssignmentGroup $AssignmentGroup
     * @param Enrollment $enrollment
     * @return array|false[]
     */
    public function getCourseScoresByUser(Course          $course,
                                          Extension       $extension,
                                          Score           $Score,
                                          Submission      $Submission,
                                          Solution        $Solution,
                                          AssignmentGroup $AssignmentGroup,
                                          Enrollment      $enrollment): array
    {


        //student in course AND allowed to view the final average
        $authorized = Gate::inspect('viewCourseScoresByUser', $course);


        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        $user = request()->user();

        //get all assignments in the course
        $Assignment = new Assignment();
        $assignments_info = $Assignment->getAssignmentsByCourse($course,
            $extension,
            $Score,
            $Submission,
            $Solution,
            $AssignmentGroup);

        usort($assignments_info['assignments'], function ($b, $a) {
            return $a['due']['due_date'] <=> $b['due']['due_date'];
        });
        if ($assignments_info ['type'] === 'error') {
            return $assignments_info;
        }
        //probably can refactor...
        $assignments = $course->assignments;

        $weighted_score = false;
        $letter_grade = false;
        $z_score = false;
        $score_info_by_assignment_group = [];
        if (!$assignments->isEmpty()) {
            $assignment_ids = $this->getAssignmentIds($assignments);
            $total_points_by_assignment_id = $this->getTotalPointsByAssignmentId($assignments, $assignment_ids);
            $scores = $course->scores->whereIn('assignment_id', $assignment_ids);

            $enrolled_users_by_id = [];
            $enrolled_users = $enrollment->getEnrolledUsersByRoleCourseSection(Auth::user()->role, $course, 0);
            if ($course->show_progress_report || $course->show_z_scores || $course->finalGrades->letter_grades_released || $course->students_can_view_weighted_average) {
                foreach ($enrolled_users as $enrolled_user) {//ignore the test student
                    $enrolled_users_by_id[$enrolled_user->id] =
                        ['name' => "$enrolled_user->first_name $enrolled_user->last_name",
                            'email' => $user->email,
                            'student_id' => $user->student_id];
                }
                [$rows, $fields, $download_rows, $download_fields, $extra_credit, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id, $sum_of_scores_by_user_and_assignment_group] = $this->processAllScoreInfo($course, $assignments, $assignment_ids, $scores, [], $enrolled_users, $enrolled_users_by_id, $total_points_by_assignment_id);

                $assignment_groups = $AssignmentGroup->summaryFromAssignments($user, $assignments, $total_points_by_assignment_id);

                foreach ($assignment_groups as $assignment_group_id => $assignment_group) {
                    $percent = isset($sum_of_scores_by_user_and_assignment_group[Auth::user()->id][$assignment_group_id]) && $assignment_group['total_points']
                        ? number_format(100 * $sum_of_scores_by_user_and_assignment_group[Auth::user()->id][$assignment_group_id] / $assignment_group['total_points'], 2) . "%"
                        : '-';
                    $score_info_by_assignment_group[] = ['assignment_group' => $assignment_group['assignment_group'],
                        'sum_of_scores' => $sum_of_scores_by_user_and_assignment_group[Auth::user()->id][$assignment_group_id] ?? '-',
                        'total_points' => $assignment_group['total_points'],
                        'percent' => $percent];
                }

                foreach ($rows as $row) {
                    if ($row['userId'] === $user->id) {
                        $z_score = $course->show_z_scores ? $row[$z_score_assignment_id] : false;
                        $letter_grade = $course->finalGrades->letter_grades_released ? $row[$letter_grade_assignment_id] : false;
                        $weighted_score = $course->students_can_view_weighted_average ? $row[$weighted_score_assignment_id] : false;
                        break;
                    }
                }
            }

        }

        $response['assignments'] = $assignments_info['assignments'];
        $response['course'] = [
            'name' => $course->name,
            'students_can_view_weighted_average' => $course->students_can_view_weighted_average,
            'letter_grades_released' => $course->finalGrades->letter_grades_released
        ];
        $response['weighted_score'] = $course->students_can_view_weighted_average ? $weighted_score : false;
        $response['letter_grade'] = $course->finalGrades->letter_grades_released ? $letter_grade : false;
        $response['z_score'] = $course->show_z_scores ? $z_score : false;
        $response['show_progress_report'] = $course->show_progress_report;
        $response['score_info_by_assignment_group'] = $score_info_by_assignment_group;
        $response['type'] = 'success';

        return $response;

    }

    /**
     *
     * The final average is computed using assignments that have "include_in_weighted_average" set to true.
     * Two items are updated with this: the counts (getAssignmentGroupWeights)
     * and whether to contribute the score: processAllScoreInfo
     * @param $course
     * @param $assignments
     * @param $assignment_ids
     * @param $scores
     * @param $extensions
     * @param $enrolled_users
     * @param $enrolled_users_by_id
     * @param $total_points_by_assignment_id
     * @return array
     */
    function processAllScoreInfo($course,
                                 $assignments,
                                 $assignment_ids,
                                 $scores, $extensions,
                                 $enrolled_users,
                                 $enrolled_users_by_id,
                                 $total_points_by_assignment_id): array
    {

        $include_in_weighted_average_by_assignment_id_and_user_id = [];
        $assign_tos = $course->assignTosByAssignmentAndUser();
        $enrolled_user_ids = array_keys($enrolled_users_by_id);

        foreach ($assignments as $assignment) {
            foreach ($enrolled_user_ids as $user_id) {
                $include_in_weighted_average_by_assignment_id_and_user_id[$assignment->id][$user_id] = $assignment->include_in_weighted_average * isset($assign_tos[$assignment->id]) && in_array($user_id, $assign_tos[$assignment->id]);
            }
        }


        [$assignment_group_weights_info, $assignment_groups_by_assignment_id] = $this->getAssignmentGroupWeights($enrolled_user_ids, $course->id, $include_in_weighted_average_by_assignment_id_and_user_id);
        [$scores_by_user_and_assignment, $proportion_scores_by_user_and_assignment_group, $sum_of_scores_by_user_and_assignment_group] = $this->getScoresByUserIdAndAssignment($course, $scores, $assignment_groups_by_assignment_id, $total_points_by_assignment_id, $include_in_weighted_average_by_assignment_id_and_user_id);
        $final_weighted_scores_and_letter_grades = $this->getFinalWeightedScoresAndLetterGrades($course, $enrolled_users, $proportion_scores_by_user_and_assignment_group, $assignment_group_weights_info);

        [$rows, $fields, $download_rows, $download_fields, $extra_credit_assignment_id, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id] = $this->getFinalTableInfo(
            $course,
            $assignment_ids,
            $enrolled_users_by_id,
            $assignments,
            $extensions,
            $final_weighted_scores_and_letter_grades['extra_credit'],
            $final_weighted_scores_and_letter_grades['final_weighted_scores'],
            $final_weighted_scores_and_letter_grades['letter_grades'],
            $final_weighted_scores_and_letter_grades['final_weighted_scores_without_extra_credit'],
            $scores_by_user_and_assignment,
            $total_points_by_assignment_id);

        return [$rows, $fields, $download_rows, $download_fields, $extra_credit_assignment_id, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id, $sum_of_scores_by_user_and_assignment_group];


    }

    /**
     * @param Course $course
     * @param int $sectionId
     * @param Enrollment $enrollment
     * @param AssignmentGroup $assignmentGroup
     * @return array|false[]
     */
    public function index(Course          $course,
                          int             $sectionId,
                          Enrollment      $enrollment,
                          AssignmentGroup $assignmentGroup)
    {

        $authorized = Gate::inspect('viewCourseScores', $course);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        //get all user_ids for the user enrolled in the course
        $enrolled_users_by_id = [];


        $role = Auth::user()->role;
        $viewable_users = $enrollment->getEnrolledUsersByRoleCourseSection($role, $course, $sectionId);
        $viewable_users_by_id = [];

        foreach ($viewable_users as $viewable_user) {
            $viewable_users_by_id[] = $viewable_user->id;
        }
        $enrolled_users = $course->enrolledUsers;
        $course_section_enrollments_by_user = $course->sectionEnrollmentsByUser();

        $ferpa_mode = (int)request()->cookie('ferpa_mode') === 1 && Auth::user()->id === 5;
        $faker = \Faker\Factory::create();
        foreach ($enrolled_users as $user) {
            $first_name = $ferpa_mode ? $faker->firstName : $user->first_name;
            $last_name = $ferpa_mode ? $faker->lastName : $user->last_name;
            $student_id = $ferpa_mode ? rand(pow(10, 4), pow(10, 4) - 1) : $user->student_id;
            $email = $ferpa_mode ? $faker->email : $user->email;
            $enrolled_users_by_id[$user->id] = ['name' => "$first_name $last_name",
                'email' => $email,
                'crn' => $course_section_enrollments_by_user[$user->id]['crn'],
                'first_name' => $first_name,
                'last_name' => $last_name,
                'student_id' => $student_id,
                'course_section' => $course_section_enrollments_by_user[$user->id]['course_section']];
        }

        //get all assignments in the course
        $assignments = $course->assignments->sortBy('due');
        if ($assignments->isEmpty()) {
            return ['hasAssignments' => false];
        }
        $assignments = $assignments->sortBy(function ($assignment) {
            return [
                $assignment->assignment_group_id,
                $assignment->order
            ];
        });


        $sections_info = (Auth::user()->role === 2) ? $course->sections : $course->graderSections();
        $sections = [];
        foreach ($sections_info as $key => $section) {
            $sections[] = ['name' => $section->name, 'id' => $section->id];
        }
        $assignment_ids = $this->getAssignmentIds($assignments);
        $total_points_by_assignment_id = $this->getTotalPointsByAssignmentId($assignments, $assignment_ids);


        $assignment_groups = $assignmentGroup->summaryFromAssignments(request()->user(), $assignments, $total_points_by_assignment_id);


        $scores = $course->scores;

        $extensions = [];
        foreach ($course->extensions as $value) {
            $extensions[$value->user_id][$value->assignment_id] = 'Extension';
        }

        [$rows, $fields, $download_rows, $download_fields, $extra_credit_assignment_id, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id, $sum_of_scores_by_user_and_assignment_group] = $this->processAllScoreInfo($course, $assignments, $assignment_ids, $scores, $extensions, $enrolled_users, $enrolled_users_by_id, $total_points_by_assignment_id);

        $viewable_rows = [];
        $viewable_download_rows = [];

        foreach ($rows as $key => $row) {
            if (in_array($row['userId'], $viewable_users_by_id)) {
                $viewable_rows[] = $row;
                $viewable_download_rows[] = $download_rows[$key];
            }
        }
        $score_info_by_assignment_group = [];
        foreach ($enrolled_users_by_id as $user_id => $user) {
            $score_info_by_assignment_group[$user_id] = ['name' => $user['name'],
                'user_id' => $user_id,
                'email' => $user['email']
            ];
            foreach ($assignment_groups as $assignment_group_id => $assignment_group) {
                $score_info_by_assignment_group[$user_id][$assignment_group['assignment_group']] = $sum_of_scores_by_user_and_assignment_group[$user_id][$assignment_group_id] ?? '-';
            }
        }
        $score_info_by_assignment_group = array_values($score_info_by_assignment_group);
        if ($score_info_by_assignment_group) {
            usort($score_info_by_assignment_group, function ($a, $b) {
                return $a['name'] <=> $b['name'];
            });
        }
        $score_info_by_assignment_group_fields = ['name', 'email'];
        foreach ($assignment_groups as $assignment_group) {
            $score_info_by_assignment_group_fields[] = $assignment_group['assignment_group'];
        }


        return ['hasAssignments' => true,
            'sections' => $sections,
            'table' => ['rows' => $viewable_rows,
                'fields' => $fields,
                'hasAssignments' => true],
            'download_fields' => $download_fields,
            'download_rows' => $viewable_download_rows,
            'extra_credit_assignment_id' => $extra_credit_assignment_id,
            'weighted_score_assignment_id' => $weighted_score_assignment_id,//needed for testing...
            'z_score_assignment_id' => $z_score_assignment_id,
            'letter_grade_assignment_id' => $letter_grade_assignment_id,
            'assignment_groups' => array_values($assignment_groups),
            'score_info_by_assignment_group' => $score_info_by_assignment_group,
            'score_info_by_assignment_group_fields' => $score_info_by_assignment_group_fields];
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param User $user
     * @param Score $score
     * @param LtiGradePassback $ltiGradePassback
     * @return array
     * @throws Exception
     */
    public
    function update(Request          $request,
                    Assignment       $assignment,
                    User             $user,
                    Score            $score,
                    LtiGradePassback $ltiGradePassback)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$score, $assignment->id, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        $validator = Validator::make($request->all(), [
            'score' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            $response['message'] = $validator->errors()->first('score');
            return $response;
        }


        try {
            DB::beginTransaction();

            $current_score = Score::where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();
            $score_updated = !$current_score || floatval($current_score->score) !== floatval($request->score);
            Score::updateOrCreate(
                ['user_id' => $user->id, 'assignment_id' => $assignment->id],
                ['score' => $request->score]
            );

            $ltiLaunch = DB::table('lti_launches')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();
            if ($ltiLaunch) {
                $ltiGradePassback->initPassBackByUserIdAndAssignmentId($request->score, $ltiLaunch);
            }
            DB::commit();
            $response['type'] = $score_updated ? 'success' : 'info';
            $response['message'] = $score_updated
                ? "The score on $assignment->name for $user->first_name $user->last_name has been updated."
                : "The score was not updated.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the score.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param User $user
     * @param Score $Score
     * @param Extension $extension
     * @return array
     * @throws Exception
     */
    public function getScoreByAssignmentAndStudent(Assignment $assignment,
                                                   User       $user,
                                                   Score      $Score,
                                                   Extension  $extension)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getScoreByAssignmentAndStudent', [$Score, $assignment->id, $user->id]);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $score = $Score->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();
            $response = $extension->show($assignment, $user);

            $response['score'] = $score ? $score->score : null;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the score and extension.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}

<?php

namespace App\Http\Controllers;

use App\AssignmentGroup;
use App\Enrollment;
use App\Extension;
use App\Grader;
use App\LtiGradePassback;
use App\LtiLaunch;
use App\Score;
use App\Course;
use App\Section;
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
use Illuminate\Support\Facades\Validator;

ini_set('max_execution_time', 300);

class ScoreController extends Controller
{
    use Statistics;


    public function getTotalPointsByAssignmentId(array $assignment_ids)
    {
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
            $total_points_by_assignment_id[$value->assignment_id] = $value->sum;
        }
        foreach ($external_total_points as $key => $value) {
            $total_points_by_assignment_id[$value->id] = $value->external_source_points;
        }

        return $total_points_by_assignment_id;
    }

    /** get the counts and the weights for each group
     *
     * @param $assignments
     * @param $enrolled_user_ids
     * @param int $course_id
     * @param $include_in_weighted_average_by_assignment_id_and_user_id
     * @return array[]
     */
    public function getAssignmentGroupWeights( $enrolled_user_ids, int $course_id, $include_in_weighted_average_by_assignment_id_and_user_id)
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
//dd( $include_in_weighted_average);

        foreach ($assignment_group_weights as $key => $value) {
            foreach ($enrolled_user_ids as $user_id) {
                $assignment_groups_by_assignment_id[$value->id] = $value->assignment_group_id;
              if (!isset($assignment_group_weights_info[$value->assignment_group_id])){
                  $assignment_group_weights_info[$value->assignment_group_id]= [];
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

    public function getScoresByUserIdAndAssignment(Course $course, $scores, array $assignment_groups_by_assignment_id, array $total_points_by_assignment_id, array $include_in_weighted_average_by_assignment_id_and_user_id)
    {

        //organize the scores by user_id and assignment
        $scores_by_user_and_assignment = [];
        $proportion_scores_by_user_and_assignment_group = [];

       $fake_student_ids = $course->fakeStudentIds();
        foreach ($scores as $score) {
            $user_id = $score->user_id;
            if (in_array($user_id, $fake_student_ids)){
                continue;
            }
            $assignment_id = $score->assignment_id;
            $scores_by_user_and_assignment[$user_id][$assignment_id] = $score->score;
            $group_id = $assignment_groups_by_assignment_id[$assignment_id];
            //init if needed
            $proportion_scores_by_user_and_assignment_group[$user_id][$group_id] = $proportion_scores_by_user_and_assignment_group[$user_id][$group_id] ?? 0;

            $score_as_proportion = (($total_points_by_assignment_id[$assignment_id]) <= 0)//total_points_by_assignment can be 0.00
                ? 0
                : $score->score / $total_points_by_assignment_id[$assignment_id];
            $proportion_scores_by_user_and_assignment_group[$user_id][$group_id] += $include_in_weighted_average_by_assignment_id_and_user_id[$assignment_id][$user_id]
                ? $score_as_proportion
                : 0;
        }

        return [$scores_by_user_and_assignment, $proportion_scores_by_user_and_assignment_group];
    }

    public function getFinalWeightedScoresAndLetterGrades(Course $course,
                                                          Collection $enrolled_users,
                                                          array $proportion_scores_by_user_and_assignment_group,
                                                          array $assignment_group_weights_info)
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

    public function getFinalTableInfo(array $assignment_ids,
                                      array $enrolled_users,
                                      array $enrolled_users_last_first,
                                      $assignments,
                                      array $extensions,
                                      array $extra_credit,
                                      array $final_weighted_scores,
                                      array $letter_grades,
                                      array $final_weighted_scores_without_extra_credit,
                                      array $scores_by_user_and_assignment,
                                      array $total_points_by_assignment_id)
    {
        {

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
            foreach ($enrolled_users as $user_id => $name) {
                $columns = [];
                $download_row_data = ['name' => $enrolled_users_last_first[$user_id]];
                foreach ($assignments as $assignment) {
                    $default_score = '-';
                    $score = $scores_by_user_and_assignment[$user_id][$assignment->id] ?? $default_score;
                    if (isset($extensions[$user_id][$assignment->id])) {
                        $score .= ' (E)';
                    }
                    $columns[$assignment->id] = $score;
                    $download_row_data["{$assignment->id}"] = str_replace(' (E)', '', $score);//get rid of the extension info
                }

                $columns[$extra_credit_assignment_id] = $extra_credit[$user_id];
                $download_row_data[$extra_credit_assignment_id] = $extra_credit[$user_id];


                $columns[$weighted_score_assignment_id] = $final_weighted_scores[$user_id];
                $download_row_data[$weighted_score_assignment_id] = $final_weighted_scores[$user_id];

                $columns[$z_score_assignment_id] = $this->computeZScore($final_weighted_scores_without_extra_credit[$user_id], $mean_and_std_dev_info);
                $download_row_data[$z_score_assignment_id] = $columns[$z_score_assignment_id];

                $columns[$letter_grade_assignment_id] = $letter_grades[$user_id];
                $download_row_data[$letter_grade_assignment_id] = $letter_grades[$user_id];


                $columns['name'] = $name;
                $columns['userId'] = $user_id;
                $download_rows[] = $download_row_data;
                $rows[] = $columns;
            }

            $fields = [['key' => 'name',
                'label' => 'Name',
                'sortable' => true,
                'stickyColumn' => true,
                'isRowHeader' => true]];
            $download_fields = new \stdClass();
            $download_fields->LastFirst = 'name';


            foreach ($assignments as $assignment) {
                $points = 0 + ($total_points_by_assignment_id[$assignment->id] ?? 0);
                $not_included = !$assignment->include_in_weighted_average ? "<span style='font-size: 12px;color:red'>*</span>" : '';
                $name_and_points = "{$assignment->name}<br><span style='font-size: 12px'>($points points)</span>$not_included";
                $field = ['key' => "$assignment->id",
                    'label' => $name_and_points];
                $download_fields->{$assignment->name} = $assignment->id;
                array_push($fields, $field);
            }
            array_push($fields, ['key' => "$extra_credit_assignment_id", 'label' => 'Extra Credit']);
            $download_fields->{"Extra Credit"} = $extra_credit_assignment_id;

            array_push($fields, ['key' => "$weighted_score_assignment_id", 'label' => 'Weighted Score']);
            $download_fields->{"Weighted Score"} = $weighted_score_assignment_id;

            array_push($fields, ['key' => "$z_score_assignment_id", 'label' => 'Z-Score']);
            $download_fields->{"Z-Score"} = $z_score_assignment_id;


            array_push($fields, ['key' => "$letter_grade_assignment_id", 'label' => 'Letter Grade']);
            $download_fields->{"Letter Grade"} = $letter_grade_assignment_id;
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

    public function getAssignmentQuestionScoresByUser(Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentQuestionScoresByUser', [$score, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $enrolled_users = [];
            foreach ($assignment->course->enrolledUsers as $key => $value) {
                $enrolled_users[$value->id] = "{$value->last_name}, {$value->first_name}";
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
                    $score = 0;
                    $score = $score
                        + ($submission_scores[$user_id][$question->id] ?? 0)
                        + ($file_submission_scores[$user_id][$question->id] ?? 0);

                    $columns[$question->id] = $score;
                    $assignment_score += $score;
                }
                $columns['name'] = $enrolled_users[$user_id];
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
    public function getCourseScoresByUser(Course $course,
                                          Extension $extension,
                                          Score $Score,
                                          Submission $Submission,
                                          Solution $Solution,
                                          AssignmentGroup $AssignmentGroup,
                                          Enrollment $enrollment)
    {


        //student in course AND allowed to view the final average
        $authorized = Gate::inspect('viewCourseScoresByUser', $course);


        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        $user = Auth::user();

        //get all assignments in the course
        $Assignment = new Assignment();
        $assignments_info = $Assignment->getAssignmentsByCourse($course,
            $extension,
            $Score, $Submission,
            $Solution,
            $AssignmentGroup);

        if ($assignments_info ['type'] === 'error') {
            return $assignments_info;
        }
        //probably can refactor...
        $assignments = $course->assignments->sortBy('due');

        if ($assignments->isEmpty()) {
            return ['hasAssignments' => false];
        }

        $assignment_ids = $this->getAssignmentIds($assignments);
        $total_points_by_assignment_id = $this->getTotalPointsByAssignmentId($assignment_ids);
        $scores = $course->scores->whereIn('assignment_id', $assignment_ids);


        $enrolled_users_last_first = [];
        $enrolled_users_by_id = [];

        $enrolled_users = $enrollment->getEnrolledUsersByRoleCourseSection(Auth::user()->role, $course, 0);

        $weighted_score = false;
        $letter_grade = false;
        $z_score = false;
        if ($course->show_z_scores || $course->finalGrades->letter_grades_released || $course->students_can_view_weighted_average) {
            foreach ($enrolled_users as $key => $enrolled_user) {//ignore the test student
                $enrolled_users_by_id[$enrolled_user->id] = "$enrolled_user->first_name $enrolled_user->last_name";
                $enrolled_users_last_first[$enrolled_user->id] = "$enrolled_user->last_name, $enrolled_user->first_name ";
            }
            [$rows, $fields, $download_rows, $download_fields, $extra_credit, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id] = $this->processAllScoreInfo($course, $assignments, $assignment_ids, $scores, [], $enrolled_users, $enrolled_users_by_id, $enrolled_users_last_first, $total_points_by_assignment_id);


            $z_score = false;


            foreach ($rows as $row) {
                if ($row['userId'] === $user->id) {
                    $z_score = $course->show_z_scores ? $row[$z_score_assignment_id] : false;
                    $letter_grade = $course->finalGrades->letter_grades_released ? $row[$letter_grade_assignment_id] : false;
                    $weighted_score = $course->students_can_view_weighted_average ? $row[$weighted_score_assignment_id] : false;
                    break;
                }
            }
        }

        $response['assignments'] = $assignments_info['assignments'];
        $response['course'] = [
            'name' => $course->name,
            'students_can_view_weighted_average' => $course->students_can_view_weighted_average,
            'letter_grades_released' => $course->finalGrades->letter_grades_released
        ];
        $response['weighted_score'] = $weighted_score;
        $response['letter_grade'] = $letter_grade;
        $response['z_score'] = $z_score;
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
     * @param $enrolled_users_last_first
     * @param $total_points_by_assignment_id
     * @return array
     */
    function processAllScoreInfo($course, $assignments, $assignment_ids, $scores, $extensions, $enrolled_users, $enrolled_users_by_id, $enrolled_users_last_first, $total_points_by_assignment_id)
    {

        $include_in_weighted_average_by_assignment_id_and_user_id = [];
        $assign_tos = $course->assignTosByAssignmentAndUser();
        $enrolled_user_ids = array_keys($enrolled_users_by_id);

        foreach ($assignments as $assignment) {
            foreach ($enrolled_user_ids  as $user_id) {
                $include_in_weighted_average_by_assignment_id_and_user_id[$assignment->id][$user_id] = $assignment->include_in_weighted_average * isset($assign_tos[$assignment->id]) && in_array($user_id, $assign_tos[$assignment->id]);
            }
        }


        [$assignment_group_weights_info, $assignment_groups_by_assignment_id] = $this->getAssignmentGroupWeights($enrolled_user_ids, $course->id, $include_in_weighted_average_by_assignment_id_and_user_id);
        [$scores_by_user_and_assignment, $proportion_scores_by_user_and_assignment_group] = $this->getScoresByUserIdAndAssignment($course, $scores, $assignment_groups_by_assignment_id, $total_points_by_assignment_id, $include_in_weighted_average_by_assignment_id_and_user_id);
        $final_weighted_scores_and_letter_grades = $this->getFinalWeightedScoresAndLetterGrades($course, $enrolled_users, $proportion_scores_by_user_and_assignment_group, $assignment_group_weights_info);

        [$rows, $fields, $download_rows, $download_fields, $extra_credit_assignment_id, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id] = $this->getFinalTableInfo(
            $assignment_ids,
            $enrolled_users_by_id,
            $enrolled_users_last_first,
            $assignments,
            $extensions,
            $final_weighted_scores_and_letter_grades['extra_credit'],
            $final_weighted_scores_and_letter_grades['final_weighted_scores'],
            $final_weighted_scores_and_letter_grades['letter_grades'],
            $final_weighted_scores_and_letter_grades['final_weighted_scores_without_extra_credit'],
            $scores_by_user_and_assignment,
            $total_points_by_assignment_id);

        return [$rows, $fields, $download_rows, $download_fields, $extra_credit_assignment_id, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id];


    }

    public function index(Course $course, int $sectionId, Enrollment $enrollment)
    {

        $authorized = Gate::inspect('viewCourseScores', $course);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        //get all user_ids for the user enrolled in the course
        $enrolled_users_by_id = [];
        $enrolled_users_last_first = [];

        $role = Auth::user()->role;
        $enrolled_users = $enrollment->getEnrolledUsersByRoleCourseSection($role, $course, $sectionId);

        foreach ($enrolled_users as $key => $user) {
            $enrolled_users_by_id[$user->id] = "$user->first_name $user->last_name";
            $enrolled_users_last_first[$user->id] = "$user->last_name, $user->first_name ";
        }

        //get all assignments in the course
        $assignments = $course->assignments->sortBy('due');

        if ($assignments->isEmpty()) {
            return ['hasAssignments' => false];
        }
        $assignments = $assignments->sortBy(function ($assignment) {
            return [
                $assignment->assignment_group_id,
                $assignment->due
            ];
        });

        $assignment_groups = [];
        foreach ($assignments as $key => $value) {
            $assignment_groups[$value->assignment_group_id][] = $value->id;
        }

        $sections_info = (Auth::user()->role === 2) ? $course->sections : $course->graderSections();
        $sections = [];
        foreach ($sections_info as $key => $section) {
            $sections[] = ['name' => $section->name, 'id' => $section->id];
        }
        $assignment_ids = $this->getAssignmentIds($assignments);
        $total_points_by_assignment_id = $this->getTotalPointsByAssignmentId($assignment_ids);

        $scores = $course->scores;

        $extensions = [];
        foreach ($course->extensions as $value) {
            $extensions[$value->user_id][$value->assignment_id] = 'Extension';
        }

        [$rows, $fields, $download_rows, $download_fields, $extra_credit_assignment_id, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id] = $this->processAllScoreInfo($course, $assignments, $assignment_ids, $scores, $extensions, $enrolled_users, $enrolled_users_by_id, $enrolled_users_last_first, $total_points_by_assignment_id);


        return ['hasAssignments' => true,
            'sections' => $sections,
            'table' => compact('rows', 'fields') + ['hasAssignments' => true],
            'download_fields' => $download_fields,
            'download_rows' => $download_rows,
            'extra_credit_assignment_id' => $extra_credit_assignment_id,
            'weighted_score_assignment_id' => $weighted_score_assignment_id,//needed for testing...
            'z_score_assignment_id' => $z_score_assignment_id,
            'letter_grade_assignment_id' => $letter_grade_assignment_id,
            'assignment_groups' => array_values($assignment_groups)];//needed for testing...
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param User $user
     * @param Score $score
     * @return mixed
     * @throws Exception
     */
    public
    function update(Request $request,
                    Assignment $assignment,
                    User $user,
                    Score $score,
                    LtiLaunch $ltiLaunch,
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
            'score' => 'required|numeric|min:0|not_in:0'
        ]);

        if ($validator->fails()) {
            $response['message'] = $validator->errors()->first('score');
            return $response;
        }


        try {
            DB::beginTransaction();
            Score::updateOrCreate(
                ['user_id' => $user->id, 'assignment_id' => $assignment->id],
                ['score' => $request->score]
            );
            $ltiGradePassback->passBackByUserIdAndAssignmentId($assignment, $user->id, $request->score, $ltiLaunch);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The score has been updated.';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the score.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getScoreByAssignmentAndStudent(Request $request, Assignment $assignment, User $user, Score $Score)
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
            $response['score'] = $score ? $score->score : 0;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the score.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}

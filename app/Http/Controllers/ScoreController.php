<?php

namespace App\Http\Controllers;

use App\AssignmentGroup;
use App\AssignmentQuestionTimeOnTask;
use App\Enrollment;
use App\Extension;
use App\Helpers\Helper;
use App\Http\Requests\UpdateScoresRequest;
use App\LtiGradePassback;
use App\ReviewHistory;
use App\Score;
use App\Course;
use App\Solution;
use App\SubmissionFile;
use App\TesterStudent;
use App\Traits\DateFormatter;
use App\User;
use App\Assignment;
use App\Submission;
use App\Question;
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

ini_set('max_execution_time', 300);

class ScoreController extends Controller
{
    use Statistics;
    use DateFormatter;

    /**
     * @param Request $request
     * @param Course $course
     * @param int $assignment_id
     * @param Score $score
     * @param Enrollment $enrollment
     * @param TesterStudent $testerStudent
     * @return array
     * @throws Exception
     */

    public function testerStudentResults(Request       $request,
                                         Course        $course,
                                         int           $assignment_id,
                                         Score         $score,
                                         Enrollment    $enrollment,
                                         TesterStudent $testerStudent
    ): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('testerStudentResults', [$score, $course, $assignment_id]);
        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $enrolled_users = $enrollment->getEnrolledUsersByRoleCourseSection($request->user()->role, $course, 0);
            $assignment_ids = $assignment_id ? [$assignment_id] : $course->assignments->pluck('id');
            $submissions = DB::table('submissions')->whereIn('assignment_id', $assignment_ids)->get();
            $submission_info = [];
            foreach ($submissions as $submission) {
                if (!isset($submission_info[$submission->user_id])) {
                    $submission_info[$submission->user_id] = ['number_submitted' => 0, 'score' => 0];
                }
                $number_submitted = isset($submission_info[$submission->user_id]['number_submitted'])
                    ? $submission_info[$submission->user_id]['number_submitted'] + 1
                    : 1;
                $score = isset($submission_info[$submission->user_id]['score'])
                    ? $submission_info[$submission->user_id]['score'] + $submission->score
                    : $submission_info[$submission->user_id]['score'];
                $submission_info[$submission->user_id] = ['number_submitted' => $number_submitted, 'score' => $score];
            }
            $student_results = [];
            $tester_students = $testerStudent
                ->where('tester_user_id', $request->user()->id);
            if ($assignment_id) {
                $tester_students = $tester_students->where('assignment_id', $assignment_id);
            }
            $tester_students = $tester_students->get()
                ->pluck('student_user_id')->toArray();

            foreach ($enrolled_users as $enrolled_user) {
                if (in_array($enrolled_user->id, $tester_students)) {
                    $student_results[] = ['id' => $enrolled_user->id,
                        'name' => "$enrolled_user->first_name $enrolled_user->last_name",
                        'student_id' => $enrolled_user->student_id,
                        'created_at' => $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($enrolled_user->created_at, $request->user()->time_zone, 'F d, Y \a\t g:i a'),
                        'number_submitted' => $submission_info[$enrolled_user->id]['number_submitted'] ?? 0,
                        'score' => $submission_info[$enrolled_user->id]['score'] ?? 0,
                    ];
                }
            }

            $response['type'] = 'success';
            $response['student_results'] = $student_results;
            $response['course'] = $course->name;
            $response['assignment'] = $assignment_id ? Assignment::find($assignment_id)->name : '';

        } catch (Exception $e) {
            $response['message'] = 'We were unable to retrieve the student scores.';
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;

    }

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

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Score $score
     * @return array
     * @throws Exception
     */
    public function uploadOverrideScores(Request $request, Assignment $assignment, Score $score): array
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
                $score = $this->convertDashToBlank($override_score['Score']);
                if ($score !== '' && (!is_numeric($score) || $score < 0)) {
                    $override_score_errors[] = $override_score['Name'];
                }
            }
            if ($override_score_errors) {
                $response['override_score_errors'] = $override_score_errors;
                return $response;
            }

            if (!(isset($override_scores[0]['User Id'])
                && isset($override_scores[0]['Name'])
                && isset($override_scores[0]['Score']))) {
                $response['message'] = "Your csv file should have UserId, Name, and Score as the first row.";
                return $response;
            }
            $from_to_scores = [];
            foreach ($override_scores as $override_score) {
                $user_id = $override_score['User Id'];
                $from_to_scores[] = ['user_id' => $override_score['User Id'],
                    'name' => $override_score['Name'],
                    'override_score' => $this->convertDashToBlank($override_score['Score']),
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

    public function convertDashToBlank($value)
    {
        return str_replace('-', '', $value);
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

    /**
     * @param Assignment $assignment
     * @param string $time_spent_option
     * @param Score $score
     * @param Enrollment $enrollment
     * @return array
     * @throws Exception
     */
    public function getAssignmentQuestionScoresByUser(Assignment $assignment,
                                                      string     $time_spent_option,
                                                      Score      $score,
                                                      Enrollment $enrollment): array
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
                $assign_to_timings_by_user = $assignment->assignToTimingsByUser();
                foreach ($viewable_users as $key => $viewable_user) {
                    if (!isset($assign_to_timings_by_user[$viewable_user->id])) {
                        unset($viewable_users [$key]);
                    }
                }
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
                $total_points_by_question_id[$value->question_id] = Helper::removeZerosAfterDecimal(round((float)$value->points, 2));
            }

            $questions = $assignment->questions;
            $rows = [];
            $time_on_tasks = DB::table('assignment_question_time_on_tasks')
                ->where('assignment_id', $assignment->id)
                ->get();
            $time_on_tasks_by_user_question = [];
            foreach ($time_on_tasks as $time_on_task) {
                $time_on_tasks_by_user_question[$time_on_task->user_id][$time_on_task->question_id] = $time_on_task->time_on_task;
            }

            $time_in_reviews = DB::table('review_histories')
                ->where('assignment_id', $assignment->id)
                ->select('user_id', 'assignment_id', 'question_id', DB::RAW('TIMESTAMPDIFF(SECOND, created_at, updated_at) AS time_in_review'))
                ->get();
            $time_in_reviews_by_user_question = [];
            foreach ($time_in_reviews as $time_in_review) {
                $time_in_reviews_by_user_question[$time_in_review->user_id][$time_in_review->question_id] =
                    isset($time_in_reviews_by_user_question[$time_in_review->user_id][$time_in_review->question_id])
                        ? $time_in_reviews_by_user_question[$time_in_review->user_id][$time_in_review->question_id] + $time_in_review->time_in_review
                        : $time_in_review->time_in_review;
            }
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
                    $time_spent = '';
                    switch ($time_spent_option) {
                        case('hidden'):
                            break;
                        case('on_task'):
                            $time_spent = $this->formatTimeSpent($time_on_tasks_by_user_question, $user_id, $question->id);
                            break;
                        case('in_review'):
                            $time_spent = $this->formatTimeSpent($time_in_reviews_by_user_question, $user_id, $question->id);
                            break;
                        case('default'):
                            throw new Exception("$time_spent_option is not a valid time spent option.");
                    }

                    $score = $score === '-' ? $score : Helper::removeZerosAfterDecimal(round((float)$score, 2));
                    $columns[$question->id] = $score . ' ' . $time_spent;
                }
                $columns['name'] = $name;
                if ($total_points) {
                    $columns['percent_correct'] = Round(100 * $assignment_score / $total_points, 1) . '%';
                    $columns['total_points'] = Helper::removeZerosAfterDecimal(round((float)$assignment_score, 2));
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
                $fields[] = $field;
            }

            if ($total_points) {
                $fields[] = ['key' => 'total_points',
                    'sortable' => true,
                    'isRowHeader' => true];
                $fields[] = ['key' => 'percent_correct',
                    'sortable' => true,
                    'isRowHeader' => true];
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
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function getCourseScoresByUser(Course          $course,
                                          Extension       $extension,
                                          Score           $Score,
                                          Submission      $Submission,
                                          Solution        $Solution,
                                          AssignmentGroup $AssignmentGroup,
                                          Enrollment      $enrollment,
                                          Assignment      $assignment): array
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


        if ($assignments_info['type'] === 'error') {
            return $assignments_info;
        }

        usort($assignments_info['assignments'], function ($b, $a) {
            return $b['order'] <=> $a['order'];
        });
        //probably can refactor...
        $assignments = $course->assignments;

        $weighted_score = false;
        $letter_grade = false;
        $z_score = false;
        $score_info_by_assignment_group = [];
        if (!$assignments->isEmpty()) {
            $assignment_ids = $assignment->getAssignmentIds($assignments);
            $total_points_by_assignment_id = $assignment->getTotalPointsByAssignmentId($assignments, $assignment_ids);
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
                [$rows, $fields, $download_rows, $download_fields, $extra_credit, $weighted_score_assignment_id, $z_score_assignment_id, $letter_grade_assignment_id, $sum_of_scores_by_user_and_assignment_group] = $Score->processAllScoreInfo($course, $assignments, $assignment_ids, $scores, [], $enrolled_users, $enrolled_users_by_id, $total_points_by_assignment_id);

                $assignment_groups = $AssignmentGroup->summaryFromAssignments($user->role, $assignments, $total_points_by_assignment_id);

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
     * @param Course $course
     * @param int $sectionId
     * @param int $download
     * @param Score $score
     * @param AssignmentQuestionTimeOnTask $assignmentQuestionTimeOnTask
     * @param ReviewHistory $reviewHistory
     * @return array|void
     */
    public function index(Course                       $course,
                          int                          $sectionId,
                          int                          $download,
                          Score                        $score,
                          AssignmentQuestionTimeOnTask $assignmentQuestionTimeOnTask,
                          ReviewHistory                $reviewHistory)
    {


        $authorized = Gate::inspect('viewCourseScores', $course);

        if (!$authorized->allowed()) {
            $response['type'] = 'error';
            $response['message'] = $authorized->message();
            return $response;
        }

        $course_scores = $score->getCourseScores($course, $sectionId);
        $assignment_time_on_tasks = $assignmentQuestionTimeOnTask->getTimeOnTaskByUserAndAssignment($course);
        $assignment_time_in_reviews = $reviewHistory->getTimeInReviewByUserAndAssignment($course);
        if ($download) {
            $download_rows = $course_scores['download_rows'];
            $download_fields = $course_scores['download_fields'];
            usort($download_rows, function ($a, $b) {
                return $a[0] <=> $b[0];
            });
            array_unshift($download_rows, $download_fields);
            $assignment_name = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $course->name);
            Helper::arrayToCsvDownload($download_rows, $assignment_name);
            exit;
        }

        return [
            'hasAssignments' => true,
            'sections' => $course_scores['sections'],
            'table' => ['rows' => $course_scores['viewable_rows'],
                'fields' => $course_scores['fields'],
                'hasAssignments' => true],
            'extra_credit_assignment_id' => $course_scores['extra_credit_assignment_id'],
            'weighted_score_assignment_id' => $course_scores['weighted_score_assignment_id'],//needed for testing...
            'z_score_assignment_id' => $course_scores['z_score_assignment_id'],
            'letter_grade_assignment_id' => $course_scores['letter_grade_assignment_id'],
            'assignment_groups' => array_values($course_scores['assignment_groups']),
            'score_info_by_assignment_group' => $course_scores['score_info_by_assignment_group'],
            'score_info_by_assignment_group_fields' => $course_scores['score_info_by_assignment_group_fields'],
            'assignment_time_on_tasks' => $assignment_time_on_tasks,
            'assignment_time_in_reviews' => $assignment_time_in_reviews
        ];
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
    public
    function getScoreByAssignmentAndStudent(Assignment $assignment,
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
            $response['assignment_name'] = $assignment->name;
            $response['score'] = $score ? $score->score : null;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the score and extension.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param array $time_spents
     * @param int $user_id
     * @param int $question_id
     * @return string
     */
    public function formatTimeSpent(array $time_spents, int $user_id, int $question_id): string
    {
        if (isset($time_spents[$user_id][$question_id])) {
            $time_spent = $time_spents[$user_id][$question_id];
            $time_spent = $this->secondsToHoursMinutesSeconds($time_spent);
            return "($time_spent)";
        } else return '';
    }


}

<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Traits\DateFormatter;
use App\Course;
use App\Solution;
use App\Score;
use App\Extension;
use App\Submission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAssignment;
use Carbon\Carbon;

use \Illuminate\Http\Request;

use App\Exceptions\Handler;
use \Exception;

class AssignmentController extends Controller
{
    use DateFormatter;

    public function solutionsReleased(Request $request, Assignment $assignment, int $solutionsReleased)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('releaseSolutions', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['solutions_released' => !$solutionsReleased]);
            $response['type'] = 'success';
            $scores_released = !$solutionsReleased ? 'released' : 'concealed';
            $response['message'] = "The solutions have been <strong>{$scores_released}</strong>.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function showScores(Request $request, Assignment $assignment, int $showScores)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showScores', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['show_scores' => !$showScores]);
            $response['type'] = 'success';
            $scores_released = !$showScores ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$scores_released}</strong> view their scores.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Display all assignments for the course
     * @param Course $course
     * @param Assignment $assignment
     * @return mixed
     */
    public function index(Course $course, Extension $extension, Score $Score, Submission $Submission, Solution $Solution)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            if (Auth::user()->role === 3) {
                $solutions_by_assignment = $Solution->getSolutionsByAssignment($course);
                $extensions_by_assignment = $extension->getUserExtensionsByAssignment(Auth::user());
                $scores_by_assignment = $Score->getUserScoresByCourse($course, Auth::user());
                $number_of_submissions_by_assignment = $Submission->getNumberOfUserSubmissionsByCourse($course, Auth::user());
            }


            $assignments = $course->assignments;

            foreach ($assignments as $key => $assignment) {
                $assignments[$key]['number_of_questions'] = count($assignment->questions);

                $available_from = $assignment['available_from'];
                if (Auth::user()->role === 3) {
                    $is_extension = isset($extensions_by_assignment[$assignment->id]);
                    $due = $is_extension ? $extensions_by_assignment[$assignment->id] : $assignment['due'];
                    $assignments[$key]['is_extension'] = isset($extensions_by_assignment[$assignment->id]);

                    $assignments[$key]['due'] = [
                        'due_date' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone), //for viewing
                        'is_extension' => $is_extension
                    ];//for viewing

                    //for comparing I just want the UTC version
                    $assignments[$key]['is_available'] = strtotime($available_from) < time();
                    $assignments[$key]['past_due'] = $due < time();
                    if (isset($scores_by_assignment[$assignment->id])) {
                        $assignments[$key]['score'] = $scores_by_assignment[$assignment->id];
                    } else {
                        $assignments[$key]['score'] = ($assignment->scoring_type === 'p') ? '0' : 'Incomplete';
                    }
                    $assignments[$key]['number_submitted'] = $number_of_submissions_by_assignment[$assignment->id];
                    $assignments[$key]['solution_key'] = $solutions_by_assignment[$assignment->id];
                } else {
                    $due = $assignment['due'];

                    $assignments[$key]['due'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone);
                    //for the editing form
                    $assignments[$key]['status'] = $this->getStatus($available_from, $due);
                    $assignments[$key]['available_from_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($available_from, Auth::user()->time_zone);
                    $assignments[$key]['available_from_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($available_from, Auth::user()->time_zone);
                    $assignments[$key]['due_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($due, Auth::user()->time_zone);
                    $assignments[$key]['due_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($due, Auth::user()->time_zone);
                    $assignments[$key]['has_submissions_or_file_submissions'] = $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty();//return as 0 or 1

                }
//same regardless of whether you're a student
                $assignments[$key]['available_from'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($available_from, Auth::user()->time_zone);


            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your assignments.  Please try again by refreshing the page or contact us for assistance.";
            return $response;
        }

        return $assignments;
    }

    function getDefaultPointsPerQuestion(array $data)
    {
        $default_points_per_question = null;
        if ($data['source'] === 'a') {
            $default_points_per_question = ($data['scoring_type'] === 'p') ? $data['default_points_per_question'] : 0;
        }
        return $default_points_per_question;
    }

    public function getStatus(string $available_from, string $due)
    {
        if (Carbon::now() < Carbon::parse($available_from) ) {
            return 'Upcoming';
        }

        if (Carbon::now() < Carbon::parse($due)) {
            return 'Open';
        }
        return 'Closed';
    }

    public function checkDueDateAfterAvailableDate(StoreAssignment $request)
    {
        $response = [];
        if (Carbon::parse($request->due) <= Carbon::parse($request->available_from)) {
            $response['available_after_due'] = true;
            $response['message'] = 'Your assignment should become due after it becomes available.';
            $response['error'] = true;
        }
        return $response;
    }

    /**
     *
     * Store a newly created resource in storage.
     * @param StoreAssignment $request
     * @param Course $course
     * @param Assignment $assignment
     * @return mixed
     * @throws Exception
     */
    public function store(StoreAssignment $request)
    {
        $response['type'] = 'error';
        $course = Course::find(['course_id' => $request->input('course_id')])->first();
        $authorized = Gate::inspect('createCourseAssignment', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';

        try {
            if ($response = $this->checkdueDateAfterAvailableDate($request)) {
                return $response;
            }
            $data = $request->validated();
            Assignment::create(
                ['name' => $data['name'],
                    'available_from' => $this->convertLocalMysqlFormattedDateToUTC($data['available_from_date'] . ' ' . $data['available_from_time'], Auth::user()->time_zone),
                    'due' => $this->convertLocalMysqlFormattedDateToUTC($data['due_date'] . ' ' . $data['due_time'], Auth::user()->time_zone),
                    'source' => $data['source'],
                    'default_points_per_question' => $this->getDefaultPointsPerQuestion($data),
                    'scoring_type' => $data['scoring_type'],
                    'submission_files' => $data['submission_files'],
                    'course_id' => $course->id
                ]
            );
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>$request->assignment</strong> has been created.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Display the specified resource
     *
     * @param Assignment $assignment
     * @return Assignment
     */
    public function show(Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment = Assignment::find($assignment->id);
            $assignment->has_submissions_or_file_submissions = $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty();
            $assignment->time_left = $this->getTimeLeft($assignment);
            $assignment->total_points = $this->getTotalPoints($assignment);
           $assignment->scores = $score->where('assignment_id', $assignment->id)->get();
            return $assignment;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";
            return $response;
        }
    }

    public function getTimeLeft(Assignment $assignment)
    {
        $Extension = new Extension();
        $extensions_by_user = $Extension->getUserExtensionsByAssignment(Auth::user());
        $due = $extensions_by_user[$assignment->id] ?? $assignment->due;
        $now = Carbon::now();
        return max($now->diffInMilliseconds(Carbon::parse($due), false), 0);

    }

    public function getTotalPoints(Assignment $assignment)
    {
        return DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->sum('points');

    }


    /**
     * @param StoreAssignment $request
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function update(StoreAssignment $request, Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            if ($response = $this->checkdueDateAfterAvailableDate($request)) {

                return $response;
            }
            $data = $request->validated();
            $data['available_from'] = $this->convertLocalMysqlFormattedDateToUTC($data['available_from_date'] . ' ' . $data['available_from_time'], Auth::user()->time_zone);

            $data['due'] = $this->convertLocalMysqlFormattedDateToUTC($data['due_date'] . ' ' . $data['due_time'], Auth::user()->time_zone);
            //remove what's not needed
            foreach (['available_from_date', 'available_from_time', 'due_date', 'due_time'] as $value) {
                unset($data[$value]);
            }
            //submissions exist so don't let them change the things below

            $data['default_points_per_question'] = $this->getDefaultPointsPerQuestion($data);
            if ($assignment->hasFileOrQuestionSubmissions()) {
                unset($data['scoring_type']);
                unset($data['default_points_per_question']);
                unset($data['submission_files']);
            }
            $assignment->update($data);
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>{$data['name']}</strong> has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>{$data['name']}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Delete an assignment
     *
     * @param Course $course
     * @param Assignment $assignment
     * @param Score $score
     * @return mixed
     * @throws Exception
     */
    public function destroy(Assignment $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::transaction(function () use ($assignment) {
                DB::table('assignment_question')->where('assignment_id', $assignment->id)->delete();
                DB::table('extensions')->where('assignment_id', $assignment->id)->delete();
                DB::table('scores')->where('assignment_id', $assignment->id)->delete();
                DB::table('submission_files')->where('assignment_id', $assignment->id)->delete();
                DB::table('submissions')->where('assignment_id', $assignment->id)->delete();
                DB::table('seeds')->where('assignment_id', $assignment->id)->delete();
                $assignment->delete();
            });
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>$assignment->name</strong> has been deleted.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$assignment->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}

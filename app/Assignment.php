<?php

namespace App;

use App\Exceptions\Handler;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\DateFormatter;

class Assignment extends Model
{
    use DateFormatter;
    protected $guarded = [];

    public function getAssignmentsByCourse(Course $course,
                                           Extension $extension,
                                           Score $Score, Submission $Submission,
                                           Solution $Solution,
                                           AssignmentGroup $AssignmentGroup)
    {
        $response['type'] = 'error';
        try {
            if (Auth::user()->role === 3) {
                $solutions_by_assignment = $Solution->getSolutionsByAssignment($course);
                $extensions_by_assignment = $extension->getUserExtensionsByAssignment(Auth::user());
                $total_points_by_assignment = $this->getTotalPointsForShownAssignments($course);
                [$scores_by_assignment, $z_scores_by_assignment] = $Score->getUserScoresByAssignment($course, Auth::user());
                $number_of_submissions_by_assignment = $Submission->getNumberOfUserSubmissionsByCourse($course, Auth::user());


            } else {
                $assignment_groups_by_assignment = $AssignmentGroup->assignmentGroupsByCourse($course->id);
            }


            $assignments = $course->assignments;
            $assignments_info = [];
            foreach ($assignments as $key => $assignment) {
                $assignments_info[$key] = $assignment->attributesToArray();
                $assignments_info[$key]['shown'] = $assignment->shown;
                $available_from = $assignment['available_from'];
                if (Auth::user()->role === 3) {
                    $is_extension = isset($extensions_by_assignment[$assignment->id]);
                    $due = $is_extension ? $extensions_by_assignment[$assignment->id] : $assignment['due'];
                    $assignments[$key]['is_extension'] = isset($extensions_by_assignment[$assignment->id]);

                    $assignments_info[$key]['due'] = [
                        'due_date' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone), //for viewing
                        'is_extension' => $is_extension
                    ];//for viewing

                    //for comparing I just want the UTC version
                    $assignments_info[$key]['is_available'] = strtotime($available_from) < time();
                    $assignments_info[$key]['past_due'] = $due < time();
                    $assignments_info[$key]['score'] = $scores_by_assignment[$assignment->id] ?? 0;

                    $assignments_info[$key]['z_score'] = $z_scores_by_assignment[$assignment->id];
                    $assignments_info[$key]['number_submitted'] = $number_of_submissions_by_assignment[$assignment->id];
                    $assignments_info[$key]['solution_key'] = $solutions_by_assignment[$assignment->id];
                    $assignments_info[$key]['total_points'] = $total_points_by_assignment[$assignment->id] ?? 0;
                } else {

                    $due = $assignment['due'];
                    $final_submission_deadline = $assignment['final_submission_deadline'];

                    $assignments_info[$key]['assignment_group'] = $assignment_groups_by_assignment[$assignment->id];
                    $assignments_info[$key]['due'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone);
                    //for the editing form
                    $editing_form_items = $this->getEditingFormItems($available_from, $due, $final_submission_deadline, $assignment);
                    foreach ($editing_form_items as $editing_form_key => $value) {
                        $assignments_info[$key][$editing_form_key] = $value;
                    }
                }
//same regardless of whether you're a student
                $assignments_info[$key]['show_points_per_question'] = $assignment->show_points_per_question;
                $assignments_info[$key]['assessment_type'] = $assignment->assessment_type;
                $assignments_info[$key]['number_of_questions'] = count($assignment->questions);
                $assignments_info[$key]['available_from'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($available_from, Auth::user()->time_zone);
                if (Auth::user()->role === 3 && !$assignments_info[$key]['shown']) {
                    unset($assignments_info[$key]);
                }
            }
            $response['assignments'] = array_values($assignments_info);//fix the unset
            $response['type'] = 'success';
        } catch
        (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your assignments.  Please try again by refreshing the page or contact us for assistance.";

        }
        return $response;
    }
    /**
     * @param Course $course
     * @return array
     */
    function getTotalPointsForShownAssignments(Course $course)
    {
        $total_points_by_assignment = [];
        $points_info = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->where('assignments.course_id', $course->id)
            ->where('assignments.shown', 1)
            ->groupBy('assignments.id')
            ->select(DB::raw('SUM(assignment_question.points) as total_points,assignments.id'))
            ->get();
        foreach ($points_info as $value) {
            $total_points_by_assignment [$value->id] = rtrim(rtrim($value->total_points, "0"), ".");
        }
        $points_info = DB::table('assignments')
            ->where('course_id', $course->id)
            ->where('source', 'x')
            ->where('shown', 1)
            ->select('id', 'external_source_points')
            ->get();
        foreach ($points_info as $value) {
            $total_points_by_assignment [$value->id] = $value->external_source_points;
        }


        return $total_points_by_assignment;


    }

    public function getStatus(string $available_from, string $due)
    {
        if (Carbon::now() < Carbon::parse($available_from)) {
            return 'Upcoming';
        }

        if (Carbon::now() < Carbon::parse($due)) {
            return 'Open';
        }
        return 'Closed';
    }
    public function getEditingFormItems(string $available_from, string $due, $final_submission_deadline, Assignment $assignment)
    {
        $editing_form_items = [];
        $editing_form_items['status'] = $this->getStatus($available_from, $due);
        $editing_form_items['available_from_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($available_from, Auth::user()->time_zone);
        $editing_form_items['available_from_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($available_from, Auth::user()->time_zone);
        $editing_form_items['final_submission_deadline_date'] = $final_submission_deadline ? $this->convertUTCMysqlFormattedDateToLocalDate($final_submission_deadline, Auth::user()->time_zone) : null;
        $editing_form_items['final_submission_deadline_time'] = $final_submission_deadline ? $this->convertUTCMysqlFormattedDateToLocalTime($final_submission_deadline, Auth::user()->time_zone) : null;
        $editing_form_items['due_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($due, Auth::user()->time_zone);
        $editing_form_items['due_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($due, Auth::user()->time_zone);
        $editing_form_items['has_submissions_or_file_submissions'] = $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty();//return as 0 or 1
        $editing_form_items['include_in_weighted_average'] = $assignment->include_in_weighted_average;
        if ($assignment->default_open_ended_submission_type === 'text') {
            $editing_form_items['default_open_ended_submission_type'] = "{$assignment->default_open_ended_text_editor} text";
        }
        return $editing_form_items;
    }

    public function getNewAssignmentOrder(Course $course)
    {
        $max_order = DB::table('assignments')
            ->where('course_id', $course->id)
            ->max('order');
        return $max_order ? $max_order + 1 : 1;
    }

    public function orderAssignments(array $ordered_assignments, Course $course)
    {
        foreach ($ordered_assignments as $key => $assignment_id) {
            DB::table('assignments')->where('course_id', $course->id)//validation step!
            ->where('id', $assignment_id)
                ->update(['order' => $key + 1]);
        }
    }

    public function questions()
    {
        return $this->belongsToMany('App\Question', 'assignment_question')
            ->withPivot('order')
            ->orderBy('assignment_question.order')
            ->withTimestamps();
    }


    public function scores()
    {
        return $this->hasMany('App\Score');
    }

    public function seeds()
    {
        return $this->hasMany('App\Seed');
    }

    public function course()
    {
        return $this->belongsTo('App\Course');
    }

    public function fileSubmissions()
    {

        return $this->hasMany('App\SubmissionFile');
    }

    public function assignmentFileSubmissions()
    {
        return $this->hasMany('App\SubmissionFile')->where('type', 'a');
    }

    public function hasFileOrQuestionSubmissions()
    {
        return $this->submissions->isNotEmpty() + $this->fileSubmissions->isNotEmpty();
    }

    public function questionFileSubmissions()
    {
        $questionFileSubmissions = DB::table('submission_files')
            ->leftJoin('users', 'grader_id', '=', 'users.id')
            ->whereIn('type', ['q', 'text', 'audio'])
            ->where('assignment_id', $this->id)
            ->select('submission_files.*', DB::raw('CONCAT(users.first_name," ", users.last_name) AS grader_name'))
            ->get();

        return collect($questionFileSubmissions);
    }

    public function learningTrees()
    {
        $learningTrees = DB::table('assignment_question')
            ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
            ->join('learning_trees', 'assignment_question_learning_tree.learning_tree_id', '=', 'learning_trees.id')
            ->where('assignment_id', $this->id)
            ->select('learning_tree', 'question_id', 'learning_tree_id')
            ->get();
        return collect($learningTrees);
    }

    public function idByCourseAssignmentUser($assignment_course_as_string)
    {
        $assignment_course_info = explode(' --- ', $assignment_course_as_string);
        if (!isset($assignment_course_info[1])) {
            return false;
        }
        $assignment = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('courses.name', $assignment_course_info[0])
            ->where('assignments.name', $assignment_course_info[1])
            ->where('courses.user_id', request()->user()->id)
            ->select('assignments.id')
            ->first();
        return $assignment ? $assignment->id : false;

    }

    public function submissions()
    {
        return $this->hasMany('App\Submission');
    }

    public function extensions()
    {
        return $this->hasMany('App\Extension');
    }


}

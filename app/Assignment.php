<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
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

    function saveAssignmentTimingAndGroup(Assignment $new_assignment)
    {

        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $new_assignment->id;
        $assignToTiming->available_from = Carbon::now()->startOfMinute()->toDateTimeString();
        $assignToTiming->due = Carbon::now()->startOfMinute()->toDateTimeString();
        if ($new_assignment->late_policy !== 'not accepted') {
            $assignToTiming->final_submission_deadline = Carbon::now()->startOfMinute()->toDateTimeString();
        }
        $assignToTiming->save();
        $assignToGroup = new AssignToGroup();
        $assignToGroup->assign_to_timing_id = $assignToTiming->id;
        $assignToGroup->group = 'course';
        $assignToGroup->group_id = $new_assignment->course_id;
        $assignToGroup->save();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function ltiLaunchExists(User $user): bool
    {
        return DB::table('lti_launches')
            ->where('assignment_id', $this->id)
            ->where('user_id', $user->id)
            ->get()
            ->isNotEmpty();

    }
    /**
     * @return array
     */
    public function ltiLaunchesByUserId(): array
    {

        $lti_launches = DB::table('lti_launches')->where('assignment_id', $this->id)->get();

        $lti_launches_by_user_id = [];
        foreach ($lti_launches as $lti_launch) {
            $lti_launches_by_user_id[$lti_launch->user_id] = $lti_launch;
        }
        return $lti_launches_by_user_id;
    }

    public function assignToTimings()
    {
        return $this->hasMany(AssignToTiming::class);
    }


    public function assignToTimingByUser($key = '')
    {
        /** $assign_to_timing = $this->assignToUsers
         * ->where('user_id', auth()->user()->id)
         * ->first();
         **/
        $assign_to_timing = DB::table('assignments')
            ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
            ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
            ->where('assignment_id', $this->id)
            ->where('user_id', auth()->user()->id)
            ->first();

        if (!$assign_to_timing) {
            return false;
        }
        $assign_to_timing_id = $assign_to_timing->assign_to_timing_id;
        $assign_to_timing = $this->assignToTimings->where('id', $assign_to_timing_id)->first();

        return $key ? $assign_to_timing[$key] : $assign_to_timing;

    }

    public function gradersAccess()
    {
        $graders = $this->course->graderInfo();

        $assignment_grader_accesses = DB::table('assignment_grader_access')
            ->join('users', 'assignment_grader_access.user_id', '=', 'users.id')
            ->where('assignment_id', $this->id)
            ->get();
        $assignment_grader_access_by_id = [];
        foreach ($assignment_grader_accesses as $assignment_grader_access) {
            $assignment_grader_access_by_id[$assignment_grader_access->user_id] = $assignment_grader_access->access_level;
        }
        foreach ($graders as $key => $grader) {
            $graders[$key]['access_level'] = $assignment_grader_access_by_id[$grader['user_id']] ?? -1;
        }
        return $graders;
    }

    public function isBetaAssignment()
    {
        return DB::table('beta_assignments')->where('id', $this->id)->first() !== null;
    }

    public function assignToUsers()
    {
        return $this->hasManyThrough(AssignToUser::class, AssignToTiming::class);
    }

    public function cutUps()
    {
        return $this->hasMany(Cutup::class);
    }


    public function getAssignmentsByCourse(Course          $course,
                                           Extension       $extension,
                                           Score           $Score, Submission $Submission,
                                           Solution        $Solution,
                                           AssignmentGroup $AssignmentGroup)
    {

        $response['type'] = 'error';
        $assigned_assignment_ids = [];
        $assigned_assignments = [];
        try {
            if (Auth::user()->role === 3) {
                $solutions_by_assignment = $Solution->getSolutionsByAssignment($course);
                $assigned_assignments = $course->assignedToAssignmentsByUser();
                $assigned_assignment_ids = array_keys($assigned_assignments);
                $extensions_by_assignment = $extension->getUserExtensionsByAssignment(Auth::user());
                $total_points_by_assignment = $this->getTotalPointsForShownAssignments($course);
                [$scores_by_assignment, $z_scores_by_assignment] = $Score->getUserScoresByAssignment($course, Auth::user());
                $number_of_submissions_by_assignment = $Submission->getNumberOfUserSubmissionsByCourse($course, Auth::user());

            } else {
                $assign_to_groups = $this->assignToGroupsByCourse($course);
            }


            $course_assignments = $course->assignments;
            $course_beta_assignment_ids = $course->betaAssignmentIds();
            if (Auth::user()->role === 4) {
                $accessible_assignment_ids = $course->accessbileAssignmentsByGrader(Auth::user()->id);
            }
            $assignment_groups_by_assignment = $AssignmentGroup->assignmentGroupsByCourse($course->id);
            $assignments_info = [];
            $number_of_questions = [];
            $results = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->whereIn('assignment_id', $assigned_assignment_ids)
                ->select('assignment_id', DB::raw('COUNT(*) AS num_questions'))
                ->groupBy('assignment_id')
                ->get();
            foreach ($results as $result) {
                $number_of_questions[$result->assignment_id] = $result->num_questions;
            }

            foreach ($course_assignments as $key => $assignment) {
                $number_of_questions = $number_of_questions[$assignment->id] ?? 0;
                if (Auth::user()->role === 3 && !in_array($assignment->id, $assigned_assignment_ids)) {
                    continue;
                }

                if (Auth::user()->role === 4 && !$accessible_assignment_ids[$assignment->id]) {
                    continue;
                }
                $assignments_info[$key] = $assignment->attributesToArray();
                $assignments_info[$key]['is_in_lms_course'] = $assignment->course->lms;
                $assignments_info[$key]['shown'] = $assignment->shown;
                $assignments_info[$key]['is_beta_assignment'] = in_array($assignment->id, $course_beta_assignment_ids);

                if (Auth::user()->role === 3) {
                    $is_extension = isset($extensions_by_assignment[$assignment->id]);
                    $available_from = $assigned_assignments[$assignment->id]->available_from;
                    $due = $is_extension ? $extensions_by_assignment[$assignment->id] : $assigned_assignments[$assignment->id]->due;
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
                    $assignments_info[$key]['number_of_questions'] = $assignment->number_of_randomized_assessments
                        ?: $number_of_questions;

                    $assignments_info[$key]['available_from'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($available_from, Auth::user()->time_zone);
                } else {
                    $assignments_info[$key]['assign_tos'] = array_values($assign_to_groups[$assignment->id]);
                    $num_assign_tos = 0;
                    $num_open = 0;
                    $num_closed = 0;
                    $num_upcoming = 0;
                    foreach ($assignments_info[$key]['assign_tos'] as $assign_to_key => $assign_to) {
                        $available_from = $assign_to['available_from'];
                        $due = $assign_to['due'];
                        $final_submission_deadline = $assign_to['final_submission_deadline'];
                        $status = $this->getStatus($available_from, $due);
                        switch ($status) {
                            case('Open'):
                                $num_open++;
                                break;
                            case('Closed'):
                                $num_closed++;
                                break;
                            case('Upcoming'):
                                $num_upcoming++;
                        }
                        $num_assign_tos++;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['status'] = $status;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['available_from'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($available_from, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['available_from_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($available_from, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['available_from_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($available_from, Auth::user()->time_zone);


                        $assignments_info[$key]['assign_tos'][$assign_to_key]['final_submission_deadline_date'] = ($assignment->late_policy !== 'not accepted')
                            ? $this->convertUTCMysqlFormattedDateToLocalDate($final_submission_deadline, Auth::user()->time_zone)
                            : null;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['final_submission_deadline_time'] = ($assignment->late_policy !== 'not accepted')
                            ? $this->convertUTCMysqlFormattedDateToLocalTime($final_submission_deadline, Auth::user()->time_zone)
                            : null;
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['due'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['due_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($due, Auth::user()->time_zone);
                        $assignments_info[$key]['assign_tos'][$assign_to_key]['due_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($due, Auth::user()->time_zone);
                    }
                    $assignments_info[$key]['overall_status'] = $this->getOverallStatus($num_assign_tos, $num_open, $num_closed, $num_upcoming);

                    $assignments_info[$key]['number_of_questions'] = $number_of_questions;


                }
//same regardless of whether you're a student
                $assignments_info[$key]['assignment_group'] = $assignment_groups_by_assignment[$assignment->id];
                $assignments_info[$key]['show_points_per_question'] = $assignment->show_points_per_question;
                $assignments_info[$key]['assessment_type'] = $assignment->assessment_type;


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

    function assignToGroups()
    {
        $assign_to_groups_by_course = $this->assignToGroupsByCourse($this->course);
        foreach ($assign_to_groups_by_course as $assignment_id => $assign_to_group) {
            if ($assignment_id === $this->id)
                return array_values($assign_to_group);
        }
    }

    /**
     * @param Course $course
     * @return array
     */
    function assignToGroupsByCourse(Course $course)
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();

        $assign_to_groups_info = DB::table('assignments')
            ->whereIn('assignment_id', $assignment_ids)
            ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
            ->join('assign_to_groups', 'assign_to_timings.id', '=', 'assign_to_groups.assign_to_timing_id')
            ->select('assignments.id AS assignment_id',
                'assign_to_timings.id AS assign_to_timing_id',
                'assign_to_groups.group',
                'assign_to_groups.group_id',
                'assign_to_timings.available_from',
                'assign_to_timings.due',
                'assign_to_timings.final_submission_deadline')
            ->where('assignments.course_id', $course->id)
            ->get();

        $section_ids = [];
        $user_ids = [];
        foreach ($assign_to_groups_info as $assign_to_group) {
            if ($assign_to_group->group === 'section') {
                $section_ids[] = $assign_to_group->group_id;
            }
            if ($assign_to_group->group === 'user') {
                $user_ids[] = $assign_to_group->group_id;
            }
        }

        $sections_info = DB::table('sections')
            ->whereIn('id', $section_ids)
            ->select('id', 'name')
            ->get();
        $users_info = DB::table('users')
            ->whereIn('id', $user_ids)
            ->select('id', DB::raw('CONCAT(first_name, " ", last_name) AS name'))
            ->get();
        $sections_by_id = [];
        $users_by_id = [];
        foreach ($sections_info as $section) {
            $sections_by_id[$section->id] = $section->name;
        }
        foreach ($users_info as $user) {
            $users_by_id[$user->id] = $user->name;
        }


        $assign_to_groups_by_assignment_id = [];
        foreach ($assign_to_groups_info as $assign_to_group) {
            $assignment_id = $assign_to_group->assignment_id;
            $assign_to_timing_id = $assign_to_group->assign_to_timing_id;

            if (!isset($assign_to_groups_by_assignment_id[$assignment_id])) {
                $assign_to_groups_by_assignment_id[$assignment_id] = [];
            }
            if (!isset($assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id])) {
                $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id] = [
                    'available_from' => $assign_to_group->available_from,
                    'due' => $assign_to_group->due,
                    'final_submission_deadline' => $assign_to_group->final_submission_deadline];
                $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id]['groups'] = [];
            }


            $group = 'Everybody';
            $formatted_group = ["value" => ["course_id" => $course->id], "text" => 'Everybody'];
            if ($assign_to_group->group === 'section') {
                $group = $sections_by_id[$assign_to_group->group_id];
                $formatted_group = ["value" => ["section_id" => $assign_to_group->group_id], "text" => $group];
            } else if ($assign_to_group->group === 'user') {
                $group = $users_by_id[$assign_to_group->group_id];
                $formatted_group = ["value" => ["user_id" => $assign_to_group->group_id], "text" => $group];
            }
            $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id]['groups'][] = $group;
            $assign_to_groups_by_assignment_id[$assignment_id][$assign_to_timing_id]['formatted_groups'][] = $formatted_group;
        }

        return $assign_to_groups_by_assignment_id;

    }

    function assignToGroupsByCourseAndUser(Course $course)
    {
        $assignment_ids = $course->assignments->pluck('id')->toArray();
        $assign_tos = DB::table('assign_tos')->whereIn('assignment_id', $assignment_ids)
            ->get();
        $assign_tos_by_assignment_id_and_user = [];

        foreach ($assign_tos as $assign_to) {
            $assignment_id = $assign_to->assignment_id;
            $assign_to_data = ['available_from' => $assign_to->available_from,
                'due' => $assign_to->due];
            if ($assign_to->course_id) {
                $enrollments = $course->enrollments()->pluck('user_id')->toArray();
                foreach ($enrollments as $enrollment) {
                    $assign_tos_by_assignment_id_and_user[$assignment_id][$enrollment] = $assign_to_data;
                }

            } else if ($assign_to->section_id) {

                echo "Do section ids";
            } else if ($assign_to->user_id) {

                echo "Do user ids";
            } else {
                echo "need to do this.";
            }

        }

        return $assign_tos_by_assignment_id_and_user;

    }

    /**
     * @param Course $course
     * @return array
     */
    function getTotalPointsForShownAssignments(Course $course)
    {
        $total_points_by_assignment = [];
        $randomized_point_info = $points_info = DB::table('assignments')
            ->where('assignments.course_id', $course->id)
            ->where('assignments.shown', 1)
            ->whereNotNull('assignments.number_of_randomized_assessments')
            ->select(DB::raw('number_of_randomized_assessments * default_points_per_question AS total_points'), 'id')
            ->get();

        foreach ($randomized_point_info as $value) {
            $total_points_by_assignment [$value->id] = $value->total_points;
        }

        $points_info = DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->where('assignments.course_id', $course->id)
            ->where('assignments.shown', 1)
            ->whereNull('assignments.number_of_randomized_assessments')
            ->groupBy('assignments.id')
            ->select(DB::raw('SUM(assignment_question.points) as total_points,assignments.id'))
            ->get();
        foreach ($points_info as $value) {
            $total_points_by_assignment [$value->id] = Helper::removeZerosAfterDecimal($value->total_points);
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

    public function graders()
    {
        return $this->belongsToMany('App\User', 'assignment_grader_access')
            ->withPivot('access_level')
            ->withTimestamps();

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


    public function betaAssignments()
    {
        if (!$this->course->alpha) {
            return [];
        }
        $beta_assignment_ids = DB::table('beta_assignments')
            ->where('alpha_assignment_id', $this->id)
            ->get();

        if ($beta_assignment_ids->isNotEmpty()) {
            $beta_assignment_ids = $beta_assignment_ids->pluck('id')->toArray();
        }
        return $this->whereIn('assignments.id', $beta_assignment_ids)
            ->get();
    }

    /**
     * @return array
     */
    public function betaAssignmentIds()
    {
        if (!$this->course->alpha) {
            return [];
        }
        $beta_assignments = DB::table('beta_assignments')
            ->where('alpha_assignment_id', $this->id)
            ->get();

        return $beta_assignments->isNotEmpty()
            ? $beta_assignments->pluck('id')->toArray()
            : [];

    }

    /**
     * @return Assignment[]
     */
    public function addBetaAssignments()
    {
        $assignments = [$this];
        $beta_assignments = $this->betaAssignments();
        foreach ($beta_assignments as $beta_assignment) {
            $assignments[] = $beta_assignment;
        }
        return $assignments;
    }

    /**
     * @return Assignment[]
     */
    public function addBetaAssignmentIds(): array
    {
        $assignment_ids = [$this->id];
        $beta_assignment_ids = $this->betaAssignmentIds();
        foreach ($beta_assignment_ids as $beta_assignment_id) {
            $assignment_ids[] = $beta_assignment_id;
        }
        return $assignment_ids;
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

    /**
     * @param array $assignment_ids
     * @return bool
     */
    public function hasNonFakeStudentFileOrQuestionSubmissions($assignment_ids = [])
    {
        if (!$assignment_ids) {
            $assignment_ids = [$this->id];
        }
        $submission_files_not_empty = DB::table('submission_files')
            ->join('users', 'submission_files.user_id', '=', 'users.id')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('fake_student', 0)
            ->get()
            ->isNotEmpty();

        $submissions_not_empty = DB::table('submissions')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('fake_student', 0)
            ->get()
            ->isNotEmpty();
        return $submission_files_not_empty || $submissions_not_empty;


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

    public function removeUserInfo(User             $user,
                                                    $assignments_to_remove_ids,
                                                    $assign_to_timings_to_remove_ids,
                                   Submission       $submission,
                                   SubmissionFile   $submissionFile,
                                   Score            $score,
                                   AssignToUser     $assignToUser,
                                   Extension        $extension,
                                   LtiGradePassback $ltiGradePassback,
                                   Seed             $seed)
    {
        $submission->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $submissionFile->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $score->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $assignToUser->where('user_id', $user->id)->whereIn('assign_to_timing_id', $assign_to_timings_to_remove_ids)->delete();
        $extension->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $ltiGradePassback->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
        $seed->where('user_id', $user->id)->whereIn('assignment_id', $assignments_to_remove_ids)->delete();
    }

    public function assignToTimingsByUser()
    {
        $assign_to_timings_by_user = [];
        $assign_to_timings = DB::table('assign_to_timings')
            ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
            ->where('assign_to_timings.assignment_id', $this->id)
            ->get();
        foreach ($assign_to_timings as $key => $assign_to_timing) {
            $assign_to_timings_by_user[$assign_to_timing->user_id] = $assign_to_timing;
        }
        return $assign_to_timings_by_user;
    }

    public function getOverallStatus(int $num_assign_tos, int $num_open, int $num_closed, int $num_upcoming)
    {
        if ($num_assign_tos === $num_open) {
            return 'Open';
        }

        if ($num_assign_tos === $num_closed) {
            return 'Closed';
        }
        if ($num_assign_tos === $num_upcoming) {
            return 'Upcoming';
        }
        return 'Partial';
    }

}

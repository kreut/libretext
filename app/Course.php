<?php

namespace App;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Course extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function scores()
    {
        return $this->hasManyThrough('App\Score', 'App\Assignment');
    }

    /**
     * @throws Exception
     */
    public function concludedCourses(string $operator_text, int $num_days): Collection
    {

        $concluded_courses = DB::table('courses')
            ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->select('courses.id',
                'courses.name',
                'courses.user_id',
                'courses.end_date')
            ->where('users.fake_student', 0);
        switch ($operator_text) {
            case('more-than'):
                $concluded_courses = $concluded_courses->where('end_date', '<', Carbon::now()->subDays($num_days));
                break;
            case('equals'):
                $concluded_courses = $concluded_courses->where(DB::raw('DATE(`end_date`)'), '=', Carbon::now()->subDays($num_days)->toDateString());
                break;
            default:
                throw new Exception ("$operator_text is not a valid operator.");
        }
        $concluded_courses = $concluded_courses
            ->groupBy('courses.id')
            ->orderBy('end_date', 'desc')
            ->get();
        $course_ids = [];
        foreach ($concluded_courses as $course_info) {
            $course_ids[] = $course_info->id;
        }
        $course_infos = DB::table('courses')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->select('courses.id',
                'users.email',
                'first_name',
                DB::raw('CONCAT(first_name, " " , last_name) AS instructor'))
            ->whereIn('courses.id', $course_ids)
            ->get();
        $courses = [];
        foreach ($course_infos as $course_info) {
            $courses[$course_info->id] = $course_info;
        }
        foreach ($concluded_courses as $key => $concluded_course) {
            if ($courses[$concluded_course->id]->email === 'adapt@libretexts.org') {
                unset($concluded_courses[$key]);
            } else {
                $concluded_courses[$key]->email = $courses[$concluded_course->id]->email;
                $concluded_courses[$key]->first_name = $courses[$concluded_course->id]->first_name;
                $concluded_courses[$key]->instructor = $courses[$concluded_course->id]->instructor;
            }
        }
        return $concluded_courses->values();
    }

    /**
     * @return Collection
     */
    public function betaCoursesInfo()
    {
        return DB::table('beta_courses')
            ->join('courses', 'beta_courses.id', '=', 'courses.id')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->where('alpha_course_id', $this->id)
            ->select('courses.name',
                DB::raw("CONCAT(users.first_name, ' ',users.last_name) AS user_name"),
                'users.email'
            )
            ->get();

    }

    /**
     * @return bool
     */
    public function isBetaCourse()
    {
        return DB::table('beta_courses')->where('id', $this->id)->first() !== null;

    }


    public function betaAssignmentIds()
    {
        $beta_assignment_ids = [];
        $beta_assignments = DB::table('assignments')
            ->join('beta_assignments', 'assignments.id', '=', 'beta_assignments.id')
            ->where('assignments.course_id', $this->id)
            ->get();

        if ($beta_assignments) {
            foreach ($beta_assignments as $beta_assignment) {
                $beta_assignment_ids[] = $beta_assignment->id;
            }
        }
        return $beta_assignment_ids;
    }

    public function school()
    {
        return $this->belongsTo('App\School');
    }

    public function extraCredits()
    {
        return $this->hasMany('App\ExtraCredit');
    }

    public function headGrader()
    {
        return $this->hasOne('App\HeadGrader');
    }

    public function sections()
    {
        return $this->hasMany('App\Section');
    }

    public function graderNotifications()
    {
        return $this->hasOne('App\GraderNotification');
    }

    /**
     * @return Collection
     */
    public function assignmentGroups(): Collection
    {
        $default_assignment_groups = AssignmentGroup::where('user_id', 0)->select()->get();
        $course_assignment_groups = AssignmentGroup::where('user_id', Auth::user()->id)
            ->where('course_id', $this->id)
            ->select()
            ->get();
        $assignmentGroup = new AssignmentGroup();
        return $assignmentGroup->combine($default_assignment_groups, $course_assignment_groups);

    }

    public function assignmentGroupWeights()
    {

        $assignment_group_ids = DB::table('assignments')
            ->select('assignment_group_id')
            ->where('course_id', $this->id)
            ->groupBy('assignment_group_id')
            ->select('assignment_group_id')
            ->pluck('assignment_group_id')
            ->toArray();

        return DB::table('assignment_group_weights')
            ->join('assignment_groups', 'assignment_group_weights.assignment_group_id', '=', 'assignment_groups.id')
            ->whereIn('assignment_group_id', $assignment_group_ids)
            ->where('assignment_group_weights.course_id', $this->id)
            ->groupBy('assignment_group_id', 'assignment_group_weights.assignment_group_weight')
            ->select('assignment_group_id AS id', 'assignment_groups.assignment_group', 'assignment_group_weights.assignment_group_weight')
            ->get();

    }

    public function enrolledUsers()
    {

        return $this->hasManyThrough('App\User',
            'App\Enrollment',
            'course_id', //foreign key on enrollments table
            'id', //foreign key on users table
            'id', //local key in courses table
            'user_id')
            ->where('fake_student', 0)
            ->orderBy('enrollments.id'); //local key in enrollments table
    }

    public function orderCourses(array $ordered_courses)
    {
        foreach ($ordered_courses as $key => $course_id) {
            DB::table('courses')
                ->where('id', $course_id)//validation step!
                ->update(['order' => $key + 1]);
        }
    }

    /**
     * @return array
     */
    public function sectionEnrollmentsByUser()
    {
        $enrolled_user_ids = $this->enrolledUsers->pluck('id')->toArray();
        $enrollments = DB::table('enrollments')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->where('enrollments.course_id', $this->id)
            ->whereIn('enrollments.user_id', $enrolled_user_ids)
            ->select('user_id', 'sections.name', 'sections.crn')
            ->get();
        $enrolled_users_by_section = [];
        foreach ($enrollments as $enrollment) {
            $enrolled_users_by_section[$enrollment->user_id] = [
                'crn' => $enrollment->crn,
                'course_section' => "$this->name - $enrollment->name"
            ];
        }
        return $enrolled_users_by_section;
    }

    public function enrolledUsersWithFakeStudent()
    {

        return $this->hasManyThrough('App\User',
            'App\Enrollment',
            'course_id', //foreign key on enrollments table
            'id', //foreign key on users table
            'id', //local key in courses table
            'user_id')
            ->orderBy('enrollments.id'); //local key in enrollments table
    }

    public function extensions()
    {
        return $this->hasManyThrough('App\Extension',
            'App\Assignment',
            'course_id', //foreign key on assignments table
            'assignment_id', //foreign key on extensions table
            'id', //local key in courses table
            'id'); //local key in assignments table
    }

    public function assignments()
    {
        return Auth::user() && Auth::user()->role === 3
            ? $this->hasMany('App\Assignment')
            : $this->hasMany('App\Assignment')->orderBy('order');
    }


    public function enrollments()
    {
        return $this->hasMany('App\Enrollment');
    }

    public function fakeStudent()
    {
        $fake_student_user_id = DB::table('enrollments')->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('course_id', $this->id)
            ->where('fake_student', 1)
            ->select('users.id')
            ->pluck('id')
            ->first();
        return User::find($fake_student_user_id);
    }

    public function fakeStudentIds()
    {
        return DB::table('enrollments')->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('course_id', $this->id)
            ->where('fake_student', 1)
            ->select('users.id')
            ->get()
            ->pluck('id')
            ->toArray();
    }


    public function finalGrades()
    {
        return $this->hasOne('App\FinalGrade');
    }

    public function graderSections($user = null)
    {
        $user = ($user === null) ? Auth::user() : $user;
        return DB::table('graders')
            ->join('sections', 'graders.section_id', '=', 'sections.id')
            ->where('sections.course_id', $this->id)
            ->where('graders.user_id', $user->id)
            ->select('sections.*')
            ->orderBy('sections.name')
            ->get();


    }

    /**
     * @param int $user_id
     * @return array
     */
    public function accessbileAssignmentsByGrader(int $user_id)
    {


        $cannot_access_assignments = DB::table('assignment_grader_access')
            ->whereIn('assignment_id', $this->assignments->pluck('id')->toArray())
            ->where('user_id', $user_id)
            ->where('access_level', 0)
            ->select('assignment_id')
            ->get();
        $cannot_access_assignment_ids = [];
        foreach ($cannot_access_assignments as $cannot_access_assignment) {
            $cannot_access_assignment_ids[] = $cannot_access_assignment->assignment_id;
        }
        $accessible_assignment_ids = [];

        foreach ($this->assignments as $assignment) {

            $accessible_assignment_ids[$assignment->id] = !in_array($assignment->id, $cannot_access_assignment_ids);
        }
        return $accessible_assignment_ids;

    }

    public function graders()
    {

        return DB::table('graders')
            ->join('sections', 'graders.section_id', '=', 'sections.id')
            ->join('users', 'graders.user_id', '=', 'users.id')
            ->where('sections.course_id', $this->id)
            ->select('users.id')
            ->groupBy('id')
            ->get();

    }

    public function graderInfo()
    {

        $grader_info = DB::table('graders')
            ->join('sections', 'graders.section_id', '=', 'sections.id')
            ->join('users', 'graders.user_id', '=', 'users.id')
            ->where('sections.course_id', $this->id)
            ->select('users.id AS user_id',
                DB::raw("CONCAT(users.first_name, ' ',users.last_name) AS user_name"),
                'email',
                'sections.name AS section_name',
                'sections.id as section_id')
            ->get();
        $graders = [];
        foreach ($grader_info as $grader) {
            if (!isset($graders[$grader->user_id])) {
                $graders[$grader->user_id]['user_id'] = $grader->user_id;
                $graders[$grader->user_id]['sections'] = [];
                $graders[$grader->user_id]['name'] = $grader->user_name;
                $graders[$grader->user_id]['email'] = $grader->email;
            }
            $graders[$grader->user_id]['sections'] [$grader->section_id] = $grader->section_name;
        }
        usort($graders, function ($a, $b) {
            return $a['name'] <=> $b['name'];
        });

        return array_values($graders);
    }


    /**
     * @param int $course_id
     * @param int $section_id
     * @param Enrollment $enrollment
     */
    public function enrollFakeStudent(int $course_id, int $section_id, Enrollment $enrollment)
    {
        $fake_student = new User();
        $fake_student->last_name = 'Student';
        $fake_student->first_name = 'Fake';
        $fake_student->time_zone = auth()->user()->time_zone;
        $fake_student->fake_student = 1;
        $fake_student->role = 3;
        $fake_student->save();

        //enroll the fake student
        $enrollment->user_id = $fake_student->id;
        $enrollment->section_id = $section_id;
        $enrollment->course_id = $course_id;
        $enrollment->save();
        return $enrollment;


    }

    public function isGrader()
    {
        $graders = DB::table('graders')
            ->join('sections', 'graders.section_id', '=', 'sections.id')
            ->where('sections.course_id', $this->id)
            ->select('user_id')
            ->get()
            ->pluck('user_id')
            ->toArray();
        return (in_array(Auth::user()->id, $graders));
    }

    public function assignTosByAssignmentAndUser()
    {
        $assigned_assignments = DB::table('assignments')
            ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
            ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
            ->where('assignments.course_id', $this->id)
            ->select('assignments.id AS assignment_id', 'assign_to_users.user_id AS user_id')
            ->get();
        $assigned_assignments_by_assignment_and_user_id = [];
        foreach ($assigned_assignments as $assignment) {
            $assigned_assignments_by_assignment_and_user_id[$assignment->assignment_id][] = $assignment->user_id;
        }
        return $assigned_assignments_by_assignment_and_user_id;
    }

    public function assignedToAssignmentsByUser()
    {
        $assigned_assignments = DB::table('assignments')
            ->join('assign_to_timings', 'assignments.id', '=', 'assign_to_timings.assignment_id')
            ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
            ->where('assignments.course_id', $this->id)
            ->where('assign_to_users.user_id', auth()->user()->id)
            ->get();
        $assigned_assignments_by_id = [];
        foreach ($assigned_assignments as $assignment) {
            $assigned_assignments_by_id[$assignment->assignment_id] = $assignment;
        }

        return $assigned_assignments_by_id;
    }

    /**
     * @return string
     */
    public function bulkUploadAllowed(): string
    {
        $beta_courses = DB::table('courses')
            ->join('beta_courses', 'courses.id', '=', 'beta_courses.alpha_course_id')
            ->where('courses.id', $this->id)
            ->select('courses.name as name')
            ->get();
        if ($beta_courses->isNotEmpty()) {
            return "Bulk upload is not possible for Alpha courses which already have Beta courses.  You can always make a copy of the course and upload these questions to the copied course.";
        }

        $course_enrollments = DB::table('courses')
            ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('courses.id', $this->id)
            ->where('fake_student', 0)
            ->where('courses.user_id', $this->user_id)
            ->select('courses.name as name')
            ->get();
        if ($course_enrollments->isNotEmpty()) {
            return "Bulk upload is only possible for courses without any enrollments.  Please make a copy of the course and upload these questions to the copied course.";
        }
        return '';
    }
}

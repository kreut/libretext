<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Course extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'start_date', 'end_date', 'user_id', 'shown', 'public','school_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function scores()
    {
        return $this->hasManyThrough('App\Score', 'App\Assignment');
    }

    public function school() {
        return $this->belongsTo('App\School');
    }

    public function extraCredits()
    {
        return $this->hasMany('App\ExtraCredit');
    }

    public function sections()
    {
        return $this->hasMany('App\Section');
    }

    public function assignmentGroups()
    {

        $default_assignment_groups = AssignmentGroup::where('user_id', 0)->select()->get();
        $course_assignment_groups = AssignmentGroup::where('user_id', Auth::user()->id)->where('course_id', $this->id)
            ->select()
            ->get();

        $assignment_groups = [];
        $used_assignment_groups = [];
        foreach ($default_assignment_groups as $key => $default_assignment_group) {
            $assignment_groups[] = $default_assignment_group;
            $used_assignment_groups[] = $default_assignment_group->assignment_group;
        }

        foreach ($course_assignment_groups as $key => $course_assignment_group) {
            if (!in_array($course_assignment_group->assignment_group, $used_assignment_groups)) {
                $assignment_groups[] = $course_assignment_group;
                $used_assignment_groups[] = $course_assignment_group->assignment_group;
            }
        }
        return collect($assignment_groups);
    }

    public function assignmentGroupWeights()
    {
        return DB::table('assignments')
            ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
            ->leftJoin('assignment_group_weights', 'assignment_groups.id', '=', 'assignment_group_weights.assignment_group_id')
            ->where('assignment_group_weights.course_id', $this->id)
            ->groupBy('assignment_groups.id', 'assignment_group_weights.assignment_group_weight')
            ->select('assignment_groups.id', 'assignment_groups.assignment_group', 'assignment_group_weights.assignment_group_weight')
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
        return Auth::user()->role === 3
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


        $cannot_access_assignments  = DB::table('assignment_grader_access')
            ->whereIn('assignment_id', $this->assignments->pluck('id')->toArray())
            ->where('user_id', $user_id)
            ->where('access_level',0)
            ->select('assignment_id')
            ->get();
        $cannot_access_assignment_ids = [];
        foreach ($cannot_access_assignments as $cannot_access_assignment){
            $cannot_access_assignment_ids[] = $cannot_access_assignment->assignment_id;
        }
        $accessible_assignment_ids = [];

        foreach ($this->assignments as $assignment){

            $accessible_assignment_ids[$assignment->id] = !in_array($assignment->id,  $cannot_access_assignment_ids);
        }
        return  $accessible_assignment_ids;

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


}

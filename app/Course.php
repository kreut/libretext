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
    protected $fillable = ['name', 'start_date', 'end_date', 'user_id', 'shown', 'public'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function scores()
    {
        return $this->hasManyThrough('App\Score', 'App\Assignment');
    }

    public function extraCredits()
    {
        return $this->hasMany('App\ExtraCredit');
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
            'user_id'); //local key in enrollments table
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
            ? $this->hasMany('App\Assignment')->orderBy('due')
            : $this->hasMany('App\Assignment')->orderBy('order');
    }


    public function enrollments()
    {
        return $this->hasMany('App\Enrollment');
    }

    public function accessCodes()
    {
        return $this->hasOne('App\CourseAccessCode');
    }

    public function finalGrades()
    {
        return $this->hasOne('App\FinalGrade');
    }

    public function graderNamesAndIds()
    {
        return $this->hasManyThrough('App\User', 'App\Grader', 'course_id', 'id', 'id', 'user_id');
    }

    public function graders()
    {
        return $this->hasMany('App\Grader');

    }

    /**
     * @param int $course_id
     * @param Enrollment $enrollment
     */
    public function enrollFakeStudent(int $course_id, Enrollment $enrollment)
    {
        $fake_student = new User();
        $fake_student->last_name = 'Student';
        $fake_student->first_name = 'Fake';
        $fake_student->time_zone = auth()->user()->time_zone;
        $fake_student->role = 3;
        $fake_student->save();

        //enroll the fake student
        $enrollment->create(['user_id' => $fake_student->id,
            'course_id' => $course_id]);

    }

    public function isGrader()
    {
        $tas = DB::table('graders')
            ->select('user_id')
            ->where('course_id', $this->id)
            ->get()
            ->pluck('user_id')
            ->toArray();
        return (in_array(Auth::user()->id, $tas));
    }

}

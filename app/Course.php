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
    protected $fillable = ['name', 'start_date', 'end_date', 'user_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function scores()
    {
        return $this->hasManyThrough('App\Score', 'App\Assignment');
    }

public function assignmentGroups() {
$course = $this;
   return  AssignmentGroup::where(function ($q) use ($course) {
       $q->where('user_id', 0)->orWhere(function ($q2) use ($course) {
           $q2->where('user_id', Auth::user()->id)->where('course_id', $course->id);
       });
   })->get();
}

public function assignmentGroupWeights() {
    return DB::table('assignments')
        ->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
        ->leftJoin('assignment_group_weights', 'assignment_groups.id', '=', 'assignment_group_weights.assignment_group_id')
        ->where('assignments.course_id', $this->id)
        ->groupBy('assignment_groups.id','assignment_group_weights.assignment_group_weight')
        ->select('assignment_groups.id','assignment_groups.assignment_group','assignment_group_weights.assignment_group_weight')
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
        return $this->hasMany('App\Assignment')->orderBy('due', 'asc');
    }


    public function enrollments()
    {
        return $this->hasMany('App\Enrollment');
    }

    public function accessCodes()
    {
        return $this->hasOne('App\CourseAccessCode');
    }

    public function graderNamesAndIds()
    {
        return $this->hasManyThrough('App\User', 'App\Grader', 'course_id', 'id', 'id', 'user_id');
    }

    public function graders()
    {
        return $this->hasMany('App\Grader');

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

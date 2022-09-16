<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Packback\Lti1p3\LtiGrade;

class Enrollment extends Model
{

    protected $guarded = [];

    public function removeAllRelatedEnrollmentInformation(User $user,
                                                          int $course_id,
                                                          AssignToUser  $assignToUser,
                                                          Assignment $assignment,
                                                          Submission $submission,
                                                          SubmissionFile $submissionFile,
                                                          Score $score,
                                                          Extension $extension,
                                                          LtiGradePassback $ltiGradePassback,
                                                          Seed $seed,
                                                          ExtraCredit $extraCredit,
                                                          Section $section)
    {
        $assignments_to_remove_ids = [];
        $assign_to_timings_to_remove_ids = [];
        $assignment_timings_and_assignment_info = $assignToUser->assignToTimingsAndAssignmentsByAssignmentIdByCourse($course_id);
        foreach ($assignment_timings_and_assignment_info as $value) {
            $assignments_to_remove_ids[] = $value->assignment_id;
            $assign_to_timings_to_remove_ids[] = $value->assign_to_timing_id;
        }
        $assignment->removeUserInfo($user,
            $assignments_to_remove_ids,
            $assign_to_timings_to_remove_ids,
            $submission,
            $submissionFile,
            $score,
            $assignToUser,
            $extension,
            $ltiGradePassback,
            $seed);

        $extraCredit->where('user_id', $user->id)->where('course_id', $course_id)->delete();
        DB::table('enrollments')->where('user_id', $user->id)->where('section_id', $section->id)->delete();
    }

    public function firstNonFakeStudent($section_id)
    {
        return DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('enrollments.section_id', $section_id)
            ->where('users.fake_student', 0)
            ->select('users.id')
            ->first()
            ->id;
    }


    public function fakeStudent($section_id)
    {
        return DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->where('enrollments.section_id', $section_id)
            ->where('users.fake_student', 1)
            ->select('users.id')
            ->first()
            ->id;
    }

    public function enrolledUsers()
    {
        return $this->hasMany('App\User');
    }

    /**
     * @param int $role
     * @param Course $course
     * @param int $section_id
     * @return mixed
     */
    public function getEnrolledUsersByRoleCourseSection(int $role, Course $course, int $section_id)
    {
        $enrolled_users = collect([]);
        switch ($section_id === 0) {
            case(true):
                $grader = new Grader();
                $enrolled_users = in_array($role, [2, 3, 6])
                    ? $course->enrolledUsers
                    : $grader->enrollmentsByCourse($course->id);

                break;
            case(false):
                $section = new Section();
                $enrolled_users = $section->find($section_id)->enrolledUsers;
                break;
        }
        return $enrolled_users;
    }

    /**
     * @return Collection
     */
    public function index(): Collection
    {
        return DB::table('courses')
            ->join('sections', 'courses.id', '=', 'sections.course_id')
            ->join('enrollments', 'sections.id', '=', 'enrollments.section_id')
            ->join('users', 'courses.user_id', '=', 'users.id')
            ->where('enrollments.user_id', '=', auth()->user()->id)
            ->where('courses.shown', 1)
            ->select(DB::raw('CONCAT(first_name, " " , last_name) AS instructor'),
                DB::raw('CONCAT(courses.name, " - " , sections.name) AS course_section_name'),
                'courses.start_date',
                'courses.end_date',
                'courses.id',
                'courses.public_description',
                'courses.lms')
            ->get();
    }
}
